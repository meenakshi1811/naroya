<?php

namespace App\Http\Controllers;
// date_default_timezone_set("Asia/Kolkata");

use DB;
use Auth;
use Hash;
use App;
use Mail;
use Session;
use Cookie;
use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use DateTime;
use DateTimeZone;
use App\Models\ReportSetting;

use App\Mail\Invoicemail;
use App\Mail\PlanSubscriptionMail;
use App\Models\User;
use App\Models\Clients;
use App\Models\Currency;
use App\Models\Offers;
use App\Models\Countries;
use App\Models\States;
use App\Models\Contactus;
use App\Models\Membership;
use App\Models\Features;
use App\Models\About_Advisori;
use App\Models\Messages;
use App\Models\Activities;
use App\Models\AffiliateCommision;
use App\Models\Invoices;
use App\Models\Subscriber_Categories;
use App\Models\Subscriber_Sub_Categories;
use App\Models\Client_jobs;
use App\Models\Client_Docs;
use App\Models\Applications;
use App\Models\Application_assignments;
use App\Models\Internal_Invoices;
use App\Models\Client_discussions;
use App\Models\Tickets;
use App\Models\Faq;
use App\Models\Invoice_settings;
use App\Models\DemoRequests;
use App\Models\UserRoles;
use App\Models\Referrals;
use App\Models\Used_referrals;
use App\Models\AffiliateCommissionEarnt;
use App\Models\Internal_communications;
use App\Models\Affiliates;
use App\Models\Feedbacks;
use App\Models\PaymentARs;
use Carbon\CarbonInterface;

use DataTables;
use App\Services\EmailTemplateService;

class AdminController extends Controller
{
    private function parseReportDate(?string $value, bool $isEndDate = false): Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $isEndDate ? Carbon::now()->endOfDay() : Carbon::now()->startOfDay();
        }

        try {
            $date = Carbon::createFromFormat('d-m-Y', $value);
        } catch (\Throwable $e) {
            $date = Carbon::parse($value);
        }

        return $isEndDate ? $date->endOfDay() : $date->startOfDay();
    }


    private function generateInternalInvoiceId(): string
    {
        $ch = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $id = "";
        for ($i = 0; $i < 8; $i++) {
            $id .= $ch[rand(0, strlen($ch) - 1)];
        }

        if (Internal_Invoices::where('invoice_no', '=', $id)->exists()) {
            return $this->generateInternalInvoiceId();
        }

        return $id;
    }

    private function generateInternalInvoiceToken(): string
    {
        $ch = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $token = "";
        for ($i = 0; $i < 20; $i++) {
            $token .= $ch[rand(0, strlen($ch) - 1)];
        }

        if (Internal_Invoices::where('token', '=', $token)->exists()) {
            return $this->generateInternalInvoiceToken();
        }

        return $token;
    }

    private function createAdminApInvoiceAndPayment(User $subscriber, User $company, float $amount, string $paymentMode, string $detail = 'Subscription Fees'): Internal_Invoices
    {
        $amount = round(max(0, $amount), 2);

        $internalInvoice = new Internal_Invoices();
        $internalInvoice->invoice_no = $this->generateInternalInvoiceId();
        $internalInvoice->subscriber_id = $subscriber->id;
        $internalInvoice->name = $company->organization;
        $internalInvoice->email = $company->email;
        $internalInvoice->phone = $company->phone;
        $internalInvoice->country = $company->country;
        $internalInvoice->state = $company->state;
        $internalInvoice->city = $company->city;
        $internalInvoice->pincode = $company->pincode;
        $internalInvoice->address = $company->address_line;
        $internalInvoice->logo = $company->organization_logo;
        $internalInvoice->to_name = $subscriber->name;
        $internalInvoice->to_email = $subscriber->email;
        $internalInvoice->to_phone = $subscriber->phone;
        $internalInvoice->to_country = $subscriber->country;
        $internalInvoice->to_state = $subscriber->state;
        $internalInvoice->to_city = $subscriber->city;
        $internalInvoice->to_pincode = $subscriber->pincode;
        $internalInvoice->to_address = $subscriber->address_line;
        $internalInvoice->detail = $detail;
        $internalInvoice->amount = $amount;
        $internalInvoice->discount = 0;
        $internalInvoice->tax = 0;
        $internalInvoice->total = $amount;
        $internalInvoice->status = 'Paid';
        $internalInvoice->type = 'ap';
        $internalInvoice->due_date = date('Y-m-d');
        $internalInvoice->token = $this->generateInternalInvoiceToken();
        $internalInvoice->save();

        PaymentARs::create([
            'subscriber_id' => $subscriber->id,
            'invoice_no' => $internalInvoice->invoice_no,
            'service_provider' => $company->organization ?: 'adwiseri.com',
            'service_taken' => $detail,
            'amount' => $amount,
            'paid_amount' => $amount,
            'payment_mode' => $paymentMode,
            'payment_date' => now(),
            'type' => 'ap',
        ]);

        return $internalInvoice;
    }

    private function buildInvoicePdfData(Internal_Invoices $internalInvoice, User $subscriber, User $company): object
    {
        return (object) [
            'invoice_no' => $internalInvoice->invoice_no,
            'invoice_date' => $internalInvoice->created_at,
            'due_date' => $internalInvoice->due_date,
            'status' => $internalInvoice->status,
            'detail' => $internalInvoice->detail,
            'amount' => $internalInvoice->amount,
            'discount' => $internalInvoice->discount,
            'tax' => $internalInvoice->tax,
            'total' => $internalInvoice->total,
            'currency' => 'USD',
            'name' => $subscriber->name,
            'to_email' => $subscriber->email,
            'company_name' => $company->organization ?: 'adwiseri',
            'from_email' => $company->email,
            'display_from_email' => $company->email,
            'logo_path' => !empty($company->organization_logo) ? 'web_assets/users/logos/' . $company->organization_logo : null,
        ];
    }

    public function index()
    {
        //return view('admin.web_home_page');
    }

    public function admin_dashboard()
    {
        ini_set('max_execution_time', 180); //3 minutes

        $user = Auth::user();
        if ($user) {
            $clients = Clients::get();
            $users = User::where('user_type', '=', 'User')->get();
            $subscribers = User::where('user_type', '=', 'Subscriber')->get();
            $price_plans = Membership::get();
            $invoices = Invoices::get();
            $countries = Countries::get();
            $activities = Activities::orderBy('created_at', 'desc')->limit(15)->get();
            $allactivities = Activities::get();
            $applications = Applications::get();
            $discussions = Client_discussions::get();
            $tickets = Tickets::get();
            $ticketsStatus = Tickets::get()->groupBy('status')->map(function ($group) {
                return $group->count();
            });
            // $refferals = Referrals::get();
            $refferals = Referrals::whereNotIn('type', ['one_off', 'double_term', 'cashback'])->where('type','Referral Commission')->orderBy('created_at', 'desc')->get();
            $messagings = Messages::get();
            $paymentARs =PaymentARs::get();

            $total_users = array();
            foreach ($users as $usr) {
                $categ = $usr->designation;
                $categ_app = 0;
                foreach ($users as $u) {
                    if ($categ == $u->designation) {
                        $categ_app += 1;
                    }
                }
                $total_users[$categ] = $categ_app;
            }
            $total_countries = array();
            foreach ($countries as $contry) {
                $categ = $contry->country_name;
                $categ_app = 0;
                foreach ($applications as $app) {
                    if ($categ == $app->application_country) {
                        $categ_app += 1;
                    }
                }
                $total_countries[$categ] = $categ_app;
            }
            $total_subscribers = array();
            foreach ($price_plans as $plan) {
                $categ = $plan->plan_name;
                $categ_app = 0;
                foreach ($subscribers as $subs) {
                    if ($categ == $subs->membership) {
                        $categ_app += 1;
                    }
                }
                $total_subscribers[$categ] = $categ_app;
            }
            $total_clients = array();
            foreach ($countries as $contry) {
                $categ = $contry->country_name;
                $categ_app = 0;
                foreach ($clients as $app) {
                    if ($categ == $app->country) {
                        $categ_app += 1;
                    }
                }
                $total_clients[$categ] = $categ_app;
            }
            $total_applications = array();
            foreach ($applications as $appl) {
                $categ = $appl->application_name;
                $categ_app = 0;
                foreach ($applications as $app) {
                    if ($categ == $app->application_name) {
                        $categ_app += 1;
                    }
                }
                $total_applications[$categ] = $categ_app;
            }

            $grouped_data_payment_mode = $paymentARs->groupBy('payment_mode');

            // Initialize the final result array
            $total_payments = [];

            foreach ($grouped_data_payment_mode as $payment_mode => $payments) {
                // Store the total count for each payment mode
                $total_payments[$payment_mode] = [
                    'total_transactions' => $payments->count(),
                    'total_amount' => $payments->sum('amount'), // Assuming `amount` is a column in the `PaymentARs` model
                ];
            }
            $total_tickets = array();
            foreach ($tickets as $tick) {
                $categ = $tick->status;
                $categ_app = 0;
                foreach ($tickets as $ticket) {
                    if ($categ == $ticket->status) {
                        $categ_app += 1;
                    }
                }
                $total_tickets[$categ] = $categ_app;
            }
            $total_discussions = array();
            foreach ($discussions as $disc) {
                $categ = $disc->communication_type;
                $categ_app = 0;
                foreach ($discussions as $discus) {
                    if ($categ == $discus->communication_type) {
                        $categ_app += 1;
                    }
                }
                $total_discussions[$categ] = $categ_app;
            }
            $total_activities = array();
            foreach ($allactivities as $act) {
                $categ = $act->activity_name;
                $categ_app = 0;
                foreach ($allactivities as $actv) {
                    if ($categ == $actv->activity_name) {
                        $categ_app += 1;
                    }
                }
                $total_activities[$categ] = $categ_app;
            }
            $page = "dashboard";
            return view('admin.dashboard', compact('ticketsStatus', 'messagings', 'refferals', 'user', 'tickets', 'page', 'total_users', 'total_payments', 'total_discussions', 'total_countries', 'total_subscribers', 'clients', 'users', 'subscribers', 'invoices', 'countries', 'activities', 'applications', 'total_clients', 'total_applications', 'total_tickets', 'total_activities'));
        } else {
            return redirect()->route('login');
        }
    }

    public function admin_profile()
    {
        $user = Auth::user();
        $countries = Countries::all();
        foreach ($countries as $country) {
            if ($country->country_name == $user->country) {
                $states = States::where('country_id', '=', $country->id)->get();
            }
        }
        $page = "profile";
        return view('admin.admin_profile', compact('user', 'countries', 'states', 'page'));
    }

    public function demo_requests()
    {
        $user = Auth::user();
        $demos = DemoRequests::orderBy('created_at', 'desc')->get();
        $page = "demo_request";
        return view('admin.demo_request', compact('user', 'demos', 'page'));
    }

    public function demo_status($id = null, $localtime = null)
    {
        if (!empty($id)) { //edit the page.
            $user = Auth::user();
            if ($user) {
                $demo = DemoRequests::find($id);
                if ($demo->status == "true") {
                    $demo->status = "false";
                } else {
                    $demo->status = "true";
                }
                $demo->save();
                return back()->with('status_updated', 'status is changed');
            } else {
                return redirect()->route('login');
            }
        } else { //view the page.
            return back();
        }
    }

    public function update_admin_profile(Request $request)
    {
        $user = Auth::user();
        if (isset($request->profile)) {
            $country = Countries::find($request->country);
            $user->name = $request['name'];
            $user->phone = $request['phone'];
            $user->organization = $request['organization'];
            $user->address_line = $request['address_line'];
            $user->country = $country->country_name;
            $user->state = $request['state'];
            $user->city = $request['city'];
            $user->pincode = $request['pincode'];
            $user->save();
            return back()->with('success', 'Profile updated');
            $activity = new Activities();
            $activity->user_id = $user->id;
            $activity->user_name = $user->name;
            $activity->activity_name = "Admin Profile Updated";
            $activity->activity_detail = "" . $user->name . " Updates his profile at " . $request->local_time;
            $activity->activity_icon = "user.png";
            $activity->local_time = $request->local_time;
            $activity->save();
            return back()->with('success', 'Profile Updated Successfully!');
        } elseif (isset($request->profile_image)) {
            if ($request->hasFile('profile_img')) {
                $file = $request->file('profile_img');
                $extension = $file->getClientOriginalName();
                $filename = time() . $extension;
                $file->move('web_assets/users/user' . $user->id . '/', $filename);
                $user->profile_img = $filename;
            }
            $user->save();
            $activity = new Activities();
            $activity->user_id = $user->id;
            $activity->user_name = $user->name;
            $activity->activity_name = "Admin Profile Updated";
            $activity->activity_detail = "" . $user->name . " Updates his profile at " . $request->local_time;
            $activity->activity_icon = "user.png";
            $activity->local_time = $request->local_time;
            $activity->save();
            return back()->with('success', 'Profile Updated Successfully!');
        } elseif (isset($request->logo_image)) {
            if ($request->hasFile('organization_logo')) {
                $file = $request->file('organization_logo');
                $extension = $file->getClientOriginalName();
                $filename = time() . $extension;
                $file->move('web_assets/users/user' . $user->id . '/', $filename);
                $user->organization_logo = $filename;
            }
            $user->save();
            $activity = new Activities();
            $activity->user_id = $user->id;
            $activity->user_name = $user->name;
            $activity->activity_name = "Organization Logo Updated";
            $activity->activity_detail = "" . $user->name . " Updates organization logo at " . $request->local_time;
            $activity->activity_icon = "user.png";
            $activity->local_time = $request->local_time;
            $activity->save();
            return back()->with('logo_updated', 'Logo Updated Successfully!');
        }
    }

    public function subscribers()
    {
        $subscribers = User::where('user_type', '=', 'Subscriber')->orderBy('created_at', 'desc')->get();
        $user = Auth::user();
        $page = "subscriber";
        if (request()->ajax()) {
            $startDate = $this->parseReportDate(request()->startdate);
            $endDate = $this->parseReportDate(request()->enddate, true);
            $subscribers = User::where('user_type', '=', 'Subscriber')->whereBetween('created_at', [$startDate, $endDate])->get();


            return DataTables::of($subscribers)
                ->addIndexColumn()
                ->editColumn('status', function ($row) use ($user) {
                    $html = '<a style="background:green;border-color:green;"';
                    if ($row->status == 'true') {
                        $result = 'Active';
                        // if (!$user->is_support) {
                        //     $html .= ' href="' . route("subscriber_status", $row->id) . '" ';
                        // }
                        // $html .= 'class="p-0 px-1">Active</a>';
                    } else {
                        // $html .= '<a style="background:red;border-color:red;"';
                        // if (!$user->is_support) {
                        //     $html .= ' href="' . route('subscriber_status', $row->id) . '" ';
                        // }
                        // $html .= ' class="p-0 px-1">Inactive</a>';
                        $result = 'Inactive';
                    }
                    return $result;
                })
                ->addColumn('action', function ($row) use ($user) {
                    $html = '<a style="background:transparent;border:none;" class=" p-0 m-0 text-dark" href="' . route('view_user', $row->id) . '"><i class="fa-solid fa-eye btn text-info p-1 m-0"></i></a>';

                    return $html;
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }


        return view('admin.subscribers', compact('subscribers', 'user', 'page'));
    }

    public function new_subscriber()
    {
        $subscribers = User::where('user_type', '=', 'Subscriber')->get();
        $countries = Countries::get();
        $subscriber_categories = Subscriber_Categories::get();
        $membership = Membership::get();
        $user = Auth::user();
        $tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        $page = "subscriber";
        return view('admin.add_subscriber', compact('subscribers', 'user', 'tzlist', 'page', 'countries', 'subscriber_categories', 'membership'));
    }

    public function update_subscriber($id)
    {
        $subscriber = User::find($id);
        $countries = Countries::get();
        foreach ($countries as $country) {
            if ($country->country_name == $subscriber->country) {
                $states = States::where('country_id', '=', $country->id)->get();
                break;
            } else {
                $states = array();
            }
        }
        $subscriber_categories = Subscriber_Categories::get();
        $subscriber_subcategories = Subscriber_Sub_Categories::get();
        $membership = Membership::get();
        $tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        $user = Auth::user();
        $page = "subscriber";
        return view('admin.add_subscriber', compact('subscriber', 'user', 'tzlist', 'page', 'countries', 'states', 'subscriber_subcategories', 'subscriber_categories', 'membership'));
    }

    public function register_new_subscriber(request $request)
    {
        // print_r($request->all());
        // exit();
        function get_referral()
        {
            $ch = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $ref = "";
            for ($i = 0; $i < 8; $i++) {
                $ref = $ref . $ch[rand(0, strlen($ch) - 1)];
            }
            $referal = User::where('referral', '=', $ref)->first();
            if ($referal) {
                get_referral();
            } else {
                return $ref;
            }
        }
        $membership = Membership::where('plan_name', $request['membership'])->first();
        $validityDuration = $membership ? $membership->validity : null;
        $user = Auth::user();
        if (isset($request->id)) {
            $country = Countries::find($request->country);
            $data = User::find($request->id);
            $data->name = $request['name'];
            $data->phone = $request['phone'];
            $data->category = $request['category'];
            $data->sub_category = $request['subcategory'];
            $data->other_subcategory = $request['other'];
            // Keep subscription plan unchanged when admin edits subscriber details.
            $data->membership = $data->membership;
            $data->membership_start_date = $data->membership_start_date;
            $data->membership_expiry_date = $data->membership_expiry_date;
            $data->organization = $request['organization'];
            $data->designation = $request['designation'];
            $data->dob = null;
            $data->employee_strength = $request['employee_strength'];
            $data->address_line = $request['address_line'];
            $data->country = $country->country_name;
            $data->state = $request['state'];
            $data->city = $request['city'];
            $data->pincode = $request['pincode'];
            $data->timezone = $request['timezone'];
            // print_r($requet->$data);
            // die();
            $data->save();

            $activity = new Activities();
            $activity->subscriber_id = $user->id;
            $activity->user_id = $user->id;
            $activity->user_name = $user->name;
            $activity->activity_name = "Subscriber Data Update";
            $activity->activity_detail = "Subscriber " . $request->name . " data updated by " . $user->name . " at " . date('d M, Y H:i:s');
            $activity->activity_icon = "user.png";
            $activity->save();
            try {
                Mail::to($data->email)->send(new PlanSubscriptionMail($request['name'], $data->membership, $validityDuration, 'Your Subscription Plan Has Been Updated'));
            } catch (\Throwable $e) {
                \Log::warning('Subscriber update email failed', ['subscriber_id' => $data->id, 'email' => $data->email, 'error' => $e->getMessage()]);
            }
            return redirect()->route('subscribers')->with('user_updated', "user updated successfully");
        } else {
            $data = new User();
            $this->validate(
                $request,
                [
                    'name' => 'required|string|max:255',
                    'phone' => 'required|unique:users',
                    'email' => 'required|string|email|max:255|unique:users',
                    'category' => 'required|string|max:255',
                    'subcategory' => 'required|string|max:255',
                    'organization' => 'required|string|max:255',
                    'designation' => 'required|string|max:255',
                    'country' => 'required|string|max:255',
                    'state' => 'required|string|max:255',
                    'city' => 'required|string|max:255',
                    'pincode' => 'required',
                    'password' => 'required|string|min:8',
                ]
            );
            $country = Countries::find($request->country);
            $data->user_type = "Subscriber";
            $data->name = $request['name'];
            $data->phone = $request['phone'];
            $data->email = $request['email'];
            $data->status = 'true';
            $data->category = $request['category'];
            $data->sub_category = $request['subcategory'];
            $data->other_subcategory = $request['other'];
            $data->membership = $request['membership'];
            $data->membership_type = "Free";
            $data->membership_start_date = new DateTime("now");
            $data->membership_expiry_date = (new DateTime("now"))->modify("+" .  $membership->validity . " Days");
            $data->wallet = 0;
            $data->referral = get_referral();
            $data->organization = $request['organization'];
            $data->designation = $request['designation'];
            $data->dob = null;
            $data->employee_strength = $request['employee_strength'];
            $data->address_line = $request['address_line'];
            $data->country = $country->country_name;
            $data->state = $request['state'];
            $data->city = $request['city'];
            $data->pincode = $request['pincode'];
            $data->timezone = $request['timezone'];
            $data->password = Hash::make($request['password']);
            $crcode = $country->currency;
            $currency = Currency::where('currency_code', '=', $crcode)->first();
            if ($currency) {
                $data->currency = $currency->currency_code . "(" . $currency->currency_symbol . ")";
            } else {
                $data->currency = "USD($)";
            }
            // print_r($requet->$data);
            // die();
            $data->save();
            $company = User::where('user_type', '=', 'admin')->first() ?: $user;
            $subscriptionAmount = (float) ($membership->price_per_year ?? 0);
            $internalInvoice = $this->createAdminApInvoiceAndPayment($data, $company, $subscriptionAmount, "Manual", "Subscription Fees ({$membership->plan_name})");

            $role = UserRoles::where('user_id', '=', $data->id)->get();
            if ($role) {
                foreach ($role as $r) {
                    $r->delete();
                }
            }
            $clients = new UserRoles();
            $clients->user_id = $data->id;
            // $clients->subscriber_id = '';
            $clients->name = $data->name;
            $clients->email = $data->email;
            $clients->module = "Clients";
            $clients->read_only = 1;
            $clients->write_only = 1;
            $clients->update_only = 1;
            $clients->delete_only = 1;
            $clients->read_write_only = 1;
            $clients->save();

            $applications = new UserRoles();
            $applications->user_id = $data->id;
            // $applications->subscriber_id = '';
            $applications->name = $data->name;
            $applications->email = $data->email;
            $applications->module = "Applications";
            $applications->read_only = 1;
            $applications->write_only = 1;
            $applications->update_only = 1;
            $applications->delete_only = 1;
            $applications->read_write_only = 1;
            $applications->save();

            $communication = new UserRoles();
            $communication->user_id = $data->id;
            // $communication->subscriber_id = '';
            $communication->name = $data->name;
            $communication->email = $data->email;
            $communication->module = "Communication";
            $communication->read_only = 1;
            $communication->write_only = 1;
            $communication->update_only = 1;
            $communication->delete_only = 1;
            $communication->read_write_only = 1;
            $communication->save();

            $invoices = new UserRoles();
            $invoices->user_id = $data->id;
            // $invoices->subscriber_id = '';
            $invoices->name = $data->name;
            $invoices->email = $data->email;
            $invoices->module = "Invoices";
            $invoices->read_only = 1;
            $invoices->write_only = 1;
            $invoices->update_only = 1;
            $invoices->delete_only = 1;
            $invoices->read_write_only = 1;
            $invoices->save();

            $payments = new UserRoles();
            $payments->user_id = $data->id;
            // $payments->subscriber_id = '';
            $payments->name = $data->name;
            $payments->email = $data->email;
            $payments->module = "Payments";
            $payments->read_only = 1;
            $payments->write_only = 1;
            $payments->update_only = 1;
            $payments->delete_only = 1;
            $payments->read_write_only = 1;
            $payments->save();

            $reports = new UserRoles();
            $reports->user_id = $data->id;
            // $reports->subscriber_id = '';
            $reports->name = $data->name;
            $reports->email = $data->email;
            $reports->module = "Reports";
            $reports->read_only = 1;
            $reports->write_only = 1;
            $reports->update_only = 1;
            $reports->delete_only = 1;
            $reports->read_write_only = 1;
            $reports->save();

            $subscription = new UserRoles();
            $subscription->user_id = $data->id;
            // $subscription->subscriber_id = '';
            $subscription->name = $data->name;
            $subscription->email = $data->email;
            $subscription->module = "Subscription";
            $subscription->read_only = 1;
            $subscription->write_only = 1;
            $subscription->update_only = 1;
            $subscription->delete_only = 1;
            $subscription->read_write_only = 1;
            $subscription->save();

            $settings = new UserRoles();
            $settings->user_id = $data->id;
            // $settings->subscriber_id = '';
            $settings->name = $data->name;
            $settings->email = $data->email;
            $settings->module = "Settings";
            $settings->read_only = 1;
            $settings->write_only = 1;
            $settings->update_only = 1;
            $settings->delete_only = 1;
            $settings->read_write_only = 1;
            $settings->save();

            $support = new UserRoles();
            $support->user_id = $data->id;
            // $support->subscriber_id = '';
            $support->name = $data->name;
            $support->email = $data->email;
            $support->module = "Support";
            $support->read_only = 1;
            $support->write_only = 1;
            $support->update_only = 1;
            $support->delete_only = 1;
            $support->read_write_only = 1;
            $support->save();


            $activity = new Activities();
            $activity->subscriber_id = $user->id;
            $activity->user_id = $user->id;
            $activity->user_name = $user->name;
            $activity->activity_name = "New Subscriber Added";
            $activity->activity_detail = "New Subscriber " . $request->name . " added by " . $user->name . " at " . date('d M, Y H:i:s');
            $activity->activity_icon = "user.png";
            $activity->save();
            Mail::to($request['email'])->send(new PlanSubscriptionMail(
                $request['name'],
                $request['membership'],
                $validityDuration,
                'Your Subscription Plan Has Been  Added!',
                $this->buildInvoicePdfData($internalInvoice, $data, $company)
            ));
            return redirect()->route('subscribers')->with('user_added', "user added successfully");
        }
    }

    public function subscriber_status($id = null, $localtime = null)
    {
        if (!empty($id)) { //edit the page.
            $user = Auth::user();
            if ($user) {
                $subscriber = User::find($id);
                if ($subscriber->status == "true") {
                    $subscriber->status = "false";
                    if ($subscriber->user_type == "Subscriber") {
                        $users = User::where('added_by', '=', $subscriber->id)->get();
                        if (count($users) > 0) {
                            foreach ($users as $u) {
                                $u->status = "false";
                                $u->save();
                            }
                        }
                    }
                } else {
                    $subscriber->status = "true";
                    if ($subscriber->user_type == "Subscriber") {
                        $users = User::where('added_by', '=', $subscriber->id)->get();
                        if (count($users) > 0) {
                            foreach ($users as $u) {
                                $u->status = "true";
                                $u->save();
                            }
                        }
                    }
                }
                $subscriber->save();
                $activity = new Activities();
                $activity->subscriber_id = $user->id;
                $activity->user_id = $user->id;
                $activity->user_name = $user->name;
                if ($subscriber->user_type == "Subscriber") {
                    $activity->activity_name = "Subscriber Status Updated";
                } else {
                    $activity->activity_name = "User Status Updated";
                }
                $activity->activity_detail = "" . $subscriber->name . " Status updated by " . $user->name . " at " . $localtime;
                $activity->activity_icon = "user.png";
                $activity->local_time = $localtime;
                $activity->save();
                return back()->with('status_updated', 'status is changed');
            } else {
                return redirect()->route('login');
            }
        } else { //view the page.
            return back();
        }
    }

    public function manage_users()
    {
        $siteusers = User::where('user_type', '=', 'User')->orderBy('created_at', 'desc')->get();
        $user = Auth::user();
        $page = "users";
        return view('admin.users', compact('siteusers', 'user', 'page'));
    }
    public function manage_user_reports()
    {
        $startDate = $this->parseReportDate(request()->input('startDate'));
        $endDate = $this->parseReportDate(request()->input('endDate'), true);
        $query = new User ();
        if(auth()->user()->user_type == 'Subscriber'){
            $query = $query->where('added_by',auth()->id()); 
        }
            $siteusers = $query->where('user_type', '=', 'User')
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->orderBy('created_at', 'desc')->get();
    

        return DataTables::of($siteusers)
            ->addIndexColumn()
            ->editColumn('start_date', function ($row) {
                // Format the `start_date` for better readability
                return date('d-m-Y', strtotime($row->start_date));
            })
            ->editColumn('name',function ($row) {
                // Format the `start_date` for better readability
                return $row->name.'('.$row->id.')';
            })
            ->editColumn('subscriber', function ($row) {
                // Format the `start_date` for better readability
                return $row->owner ? $row->owner->name.'('.$row->added_by.')' :'';
            })
            ->editColumn('end_date', function ($row) {
                // Format the `end_date`
                return date('d-m-Y', strtotime($row->end_date));
            })
            ->editColumn('status', function ($row) {
                // Format the `end_date`
                return $row->status ? 'Active' : 'InActive';
            })
            ->editColumn('created_at', function ($row) {
                // Format the `end_date`
                return date('d-m-Y', strtotime($row->created_at));
            })

            ->make(true);
    }

    public function new_user()
    {
        $subscribers = User::where('user_type', '=', 'Subscriber')->get();
        $countries = Countries::get();
        $subscriber_categories = Subscriber_Categories::get();
        $membership = Membership::get();
        $user = Auth::user();
        $tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        $page = "users";
        return view('admin.add_users', compact('subscribers', 'user', 'tzlist', 'page', 'countries', 'subscriber_categories', 'membership'));
    }

    public function siteuser_update($id)
    {
        $siteuser = User::find($id);
        $countries = Countries::get();
        foreach ($countries as $country) {
            if ($country->country_name == $siteuser->country) {
                $states = States::where('country_id', '=', $country->id)->get();
            }
        }
        $user = Auth::user();
        $tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        $page = "users";
        return view('admin.add_users', compact('siteuser', 'user', 'tzlist', 'page', 'countries', 'states'));
    }

    public function register_new_user(request $request)
    {
        // print_r($request->all());
        // exit();
        $user = Auth::user();
        if (isset($request->id)) {
            $country = Countries::find($request->country);
            $data = User::find($request->id);
            $data->name = $request['name'];
            $data->phone = $request['phone'];
            $data->dob = $request['dob'];
            $data->designation = $request['designation'];
            $data->address_line = $request['address_line'];
            $data->country = $country->country_name;
            $data->state = $request['state'];
            $data->city = $request['city'];
            $data->pincode = $request['pincode'];
            $data->timezone = $request['timezone'];
            // print_r($requet->$data);
            // die();
            $data->save();
            $activity = new Activities();
            $activity->subscriber_id = $user->id;
            $activity->user_id = $user->id;
            $activity->user_name = $user->name;
            $activity->activity_name = "User Data Update";
            $activity->activity_detail = "User " . $request->name . " data updated by " . $user->name . " at " . date('d M, Y H:i:s');
            $activity->activity_icon = "user.png";
            $activity->save();
            return redirect()->route('manage_users')->with('user_updated', "user updated successfully");
        } else {
            $data = new User();
            $subscriber = User::find($request->subscriber);
            $siteusers = User::where('added_by', '=', $subscriber->id)->get();
            $membership_plan = Membership::where('plan_name', '=', $subscriber->membership)->first();
            if (count($siteusers) >= $membership_plan->no_of_users) {
                return back()->with('user_limit', 'Upgrade membership to add more users.');
            }
            $this->validate(
                $request,
                [
                    'name' => 'required|string|max:255',
                    'phone' => 'required|unique:users',
                    'email' => 'required|string|email|max:255|unique:users',
                    'dob' => ['required'],
                    'designation' => 'required|string|max:255',
                    'country' => 'required|string|max:255',
                    'state' => 'required|string|max:255',
                    'city' => 'required|string|max:255',
                    'pincode' => 'required',
                    'password' => 'required|string|min:8',
                ],

            );

            $country = Countries::find($request->country);
            $data->user_type = "User";
            $data->added_by = $subscriber->id;
            $data->name = $request['name'];
            $data->phone = $request['phone'];
            $data->email = $request['email'];
            $data->dob = $request['dob'];
            $data->status = 'true';
            $data->category = $subscriber->category;
            $data->sub_category = $subscriber->sub_category;
            $data->other_subcategory = $subscriber->other_subcategory;
            $data->membership = $subscriber->membership;
            $data->membership_type = $subscriber->membership_type;
            $data->membership_start_date = $subscriber->membership_start_date;
            $data->membership_expiry_date = $subscriber->membership_expiry_date;
            $data->wallet = $subscriber->wallet;
            $data->referral = $subscriber->referral;
            $data->organization = $subscriber->organization;
            $data->designation = $request['designation'];
            $data->employee_strength = $subscriber->employee_strength;
            $data->address_line = $request['address_line'];
            $data->country = $country->country_name;
            $data->state = $request['state'];
            $data->city = $request['city'];
            $data->pincode = $request['pincode'];
            $data->timezone = $request['timezone'];
            $crcode = $country->currency;
            $currency = Currency::where('currency_code', '=', $crcode)->first();
            if ($currency) {
                $data->currency = $currency->currency_code . "(" . $currency->currency_symbol . ")";
            } else {
                $data->currency = "USD($)";
            }
            $data->password = Hash::make($request['password']);
            // print_r($requet->$data);
            // die();
            $data->save();

            $role = UserRoles::where('user_id', '=', $data->id)->get();
            if ($role) {
                foreach ($role as $r) {
                    $r->delete();
                }
            }
            $clients = new UserRoles();
            $clients->user_id = $data->id;
            $clients->subscriber_id = $data->added_by;
            $clients->name = $data->name;
            $clients->email = $data->email;
            $clients->module = "Clients";
            $clients->read_only = 1;
            $clients->write_only = 1;
            $clients->update_only = 1;
            $clients->delete_only = 1;
            $clients->read_write_only = 1;
            $clients->save();

            $applications = new UserRoles();
            $applications->user_id = $data->id;
            $applications->subscriber_id = $data->added_by;
            $applications->name = $data->name;
            $applications->email = $data->email;
            $applications->module = "Applications";
            $applications->read_only = 1;
            $applications->write_only = 1;
            $applications->update_only = 1;
            $applications->delete_only = 1;
            $applications->read_write_only = 1;
            $applications->save();

            $communication = new UserRoles();
            $communication->user_id = $data->id;
            $communication->subscriber_id = $data->added_by;
            $communication->name = $data->name;
            $communication->email = $data->email;
            $communication->module = "Communication";
            $communication->read_only = 1;
            $communication->write_only = 1;
            $communication->update_only = 1;
            $communication->delete_only = 1;
            $communication->read_write_only = 1;
            $communication->save();

            $invoices = new UserRoles();
            $invoices->user_id = $data->id;
            $invoices->subscriber_id = $data->added_by;
            $invoices->name = $data->name;
            $invoices->email = $data->email;
            $invoices->module = "Invoices";
            $invoices->read_only = 1;
            $invoices->write_only = 1;
            $invoices->update_only = 1;
            $invoices->delete_only = 1;
            $invoices->read_write_only = 1;
            $invoices->save();

            $payments = new UserRoles();
            $payments->user_id = $data->id;
            $payments->subscriber_id = $data->added_by;
            $payments->name = $data->name;
            $payments->email = $data->email;
            $payments->module = "Payments";
            $payments->read_only = 1;
            $payments->write_only = 1;
            $payments->update_only = 1;
            $payments->delete_only = 1;
            $payments->read_write_only = 1;
            $payments->save();

            $reports = new UserRoles();
            $reports->user_id = $data->id;
            $reports->subscriber_id = $data->added_by;
            $reports->name = $data->name;
            $reports->email = $data->email;
            $reports->module = "Reports";
            $reports->read_only = 0;
            $reports->write_only = 0;
            $reports->update_only = 0;
            $reports->delete_only = 0;
            $reports->read_write_only = 0;
            $reports->save();

            $subscription = new UserRoles();
            $subscription->user_id = $data->id;
            $subscription->subscriber_id = $data->added_by;
            $subscription->name = $data->name;
            $subscription->email = $data->email;
            $subscription->module = "Subscription";
            $subscription->read_only = 0;
            $subscription->write_only = 0;
            $subscription->update_only = 0;
            $subscription->delete_only = 0;
            $subscription->read_write_only = 0;
            $subscription->save();

            $settings = new UserRoles();
            $settings->user_id = $data->id;
            $settings->subscriber_id = $data->added_by;
            $settings->name = $data->name;
            $settings->email = $data->email;
            $settings->module = "Settings";
            $settings->read_only = 0;
            $settings->write_only = 0;
            $settings->update_only = 0;
            $settings->delete_only = 0;
            $settings->read_write_only = 0;
            $settings->save();

            $support = new UserRoles();
            $support->user_id = $data->id;
            $support->subscriber_id = $data->added_by;
            $support->name = $data->name;
            $support->email = $data->email;
            $support->module = "Support";
            $support->read_only = 1;
            $support->write_only = 1;
            $support->update_only = 1;
            $support->delete_only = 1;
            $support->read_write_only = 1;
            $support->save();


            $activity = new Activities();
            $activity->subscriber_id = $user->id;
            $activity->user_id = $user->id;
            $activity->user_name = $user->name;
            $activity->activity_name = "New User Added";
            $activity->activity_detail = "New User " . $request->name . " added by " . $user->name . " at " . date('d M, Y H:i:s');
            $activity->activity_icon = "user.png";
            $activity->save();
            return redirect()->route('manage_users')->with('user_added', "user added successfully");
        }
    }

    public function manage_clients()
    {
        $clients = Clients::whereNotNull('subscriber_id')->with('subscriber')->orderBy('created_at', 'desc')->get();
        $user = Auth::user();
        $subscribers = User::where('user_type', '=', 'Subscriber')->get();
        $countries = Countries::get();
        $page = "clients";
        return view('admin.clients', compact('clients', 'user', 'page','subscribers','countries'));
    }
    public function manage_clients_report()
    {
        $startInput = request()->input('startDate');
        $endInput   = request()->input('endDate');

        // Handle empty dates safely
        if (!$startInput || !$endInput) {
            return response()->json(['error' => 'Start date and end date are required'], 400);
        }

        try {
            $startDate = $this->parseDateFlexible($startInput)->startOfDay();
            $endDate   = $this->parseDateFlexible($endInput)->endOfDay();
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Invalid date format. Please use a valid date (for example: 2026-03-21, 21-03-2026, 03/21/2026, or 21 March 2026).'
            ], 400);
        }

        $user = auth()->user();
        $query = new Clients();

        if ($user->user_type == 'Subscriber') {
            $query = $query->where('subscriber_id', $user->id);
        }

        $clients = $query->whereBetween('created_at', [$startDate, $endDate])
                        ->orderBy('created_at', 'desc')
                        ->get();

        if (request()->ajax()) {
            return DataTables::of($clients)
                ->addIndexColumn()
                ->editColumn('name', function ($row) {
                    return $row->name . '(' . $row->id . ')';
                })
                ->editColumn('subscriber', function ($row) {
                    return $row->subscriber ? $row->subscriber->name . '(' . $row->subscriber->id . ')' : '';
                })
                ->addColumn('noa', function ($row) {
                    return $row->dependants ? $row->dependants()->count() : 0;
                })
                ->editColumn('created_at', function ($row) {
                    return \Carbon\Carbon::parse($row->created_at)->format('d-m-Y');
                })
                ->rawColumns(['name', 'subscriber', 'noa', 'created_at'])
                ->make(true);
        }
    }

    private function parseDateFlexible($date)
    {
        if (!$date) {
            throw new \Exception('Empty date');
        }

        try {
            $date = trim((string) $date);

            // Handle unix timestamps as well.
            if (ctype_digit($date)) {
                return \Carbon\Carbon::createFromTimestamp((int) $date);
            }

            // Normalize separator variants once so we can support slash and dot inputs.
            $normalizedDate = str_replace(['/', '.'], '-', $date);

            $supportedFormats = [
                // Numeric formats.
                'Y-m-d',
                'd-m-Y',
                'm-d-Y',
                'Y-n-j',
                'j-n-Y',
                'n-j-Y',
                // Textual month formats.
                'd M Y',
                'd F Y',
                'M d Y',
                'F d Y',
                'd M, Y',
                'd F, Y',
                'M d, Y',
                'F d, Y',
            ];

            foreach ($supportedFormats as $format) {
                try {
                    $parsedDate = \Carbon\Carbon::createFromFormat($format, $normalizedDate);
                    if ($parsedDate !== false) {
                        return $parsedDate;
                    }
                } catch (\Exception $e) {
                    // Try next format.
                }
            }

            // Last fallback for valid date strings accepted by Carbon.
            return \Carbon\Carbon::parse($date);

        } catch (\Exception $e) {
            throw new \Exception('Invalid date');
        }
    }

    public function client_documents_reports()
    {
        $startDate = $this->parseReportDate(request()->input('startDate'));
        $endDate = $this->parseReportDate(request()->input('endDate'), true);
        $query = new Client_Docs();
        if(auth()->user()->user_type == 'Subscriber'){
            $query =  $query->where('user_id',auth()->user()->id);
        }
        $clientDocs = $query->whereNotNull('application_id')->orderBy('created_at', 'desc')->whereBetween('created_at', [$startDate, $endDate])->get();
        return DataTables::of($clientDocs)
            ->addIndexColumn()
            ->editColumn('created_at', function ($row) {
                // Format the `created_at` column
                return \Carbon\Carbon::parse($row->created_at)->format('d-m-Y'); // Example: 18 Dec 2024, 02:15 PM
            })
            ->editColumn('client_id', function ($row) {
                // Format the `start_date` for better readability
                return $row->client_id ? $row->client->name . '(' . $row->client->id . ')' :'';
            })
            ->editColumn('application_id', function ($row) {
                // Format the `start_date` for better readability
                return $row->application ? $row->application->application_name . '(' . $row->application->id . ')' : $row->application_id;
            })
            ->editColumn('doc_file', function ($doc) {
                if (!empty($doc->doc_file)) {
                    $filePath = asset('web_assets/users/client' . $doc->client_id . '/docs/' . $doc->doc_file); // Generate URL
                    return '<a href="' . $filePath . '" download="' . htmlspecialchars($doc->doc_file) . '" class="p-0 m-0" style="text-decoration: none; border: none; background: none;">
                                <i class="fa-solid fa-download btn p-1 text-primary" style="font-size: 14px;"></i>
                            </a>' . $doc->doc_file;
                }
                return 'No file available';
            })
            ->editColumn('doc_size', function ($doc) {
                if (!empty($doc->doc_file)) {
                    $filePath = public_path('web_assets/users/client' . $doc->client_id . '/docs/' . $doc->doc_file);
                    $fileSize = file_exists($filePath) ? number_format(filesize($filePath) / 1024, 2) . ' KB' : '';
                    return $fileSize;
                }
                return 'No file available';
            })
            ->rawColumns(['doc_file']) // Ensure 'doc_file' column renders raw HTML
            ->make(true);
    }

    public function new_client()
    {
        $subscribers = User::where('user_type', '=', 'Subscriber')->get();
        $countries = Countries::get();
        $user = Auth::user();
        $page = "clients";
        return view('admin.add_clients', compact('subscribers', 'user', 'page', 'countries'));
    }

    public function client_update($id)
    {
        $client = Clients::find($id);
        $countries = Countries::get();
        foreach ($countries as $country) {
            if ($country->country_name == $client->country) {
                $states = States::where('country_id', '=', $country->id)->get();
            }
        }
        $user = Auth::user();
        $page = "clients";
        return view('admin.add_clients', compact('client', 'states', 'user', 'page', 'countries'));
    }

    public function register_new_client(request $request)
    {
        // print_r($request->all());
        // exit();
        function job_id()
        {
            $ch = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $id = "";
            for ($i = 0; $i < 8; $i++) {
                $id = $id . $ch[rand(0, strlen($ch) - 1)];
            }
            return $id;
        }
        $user = Auth::user();
        if (isset($request->id)) {
            $country = Countries::find($request->country);
            $nationality = Countries::find($request->nationality);
            $data = Clients::find($request->id);
            $data->name = $request['name'];
            $data->phone = $request['phone'];
            $data->nationality = $nationality->country_name;
            $data->passport_no = $request['passport_no'];
            $data->dob = $request['dob'];
            $data->address = $request['address'];
            $data->alternate_no = $request['alternate_no'];
            $data->country = $country->country_name;
            $data->state = $request['state'];
            $data->city = $request['city'];
            $data->pincode = $request['pincode'];
            // print_r($requet->$data);
            // die();
            $data->save();
            $activity = new Activities();
            $activity->subscriber_id = $user->id;
            $activity->user_id = $user->id;
            $activity->user_name = $user->name;
            $activity->activity_name = "Client Data Update";
            $activity->activity_detail = "Client " . $request->name . " data updated by " . $user->name . " at " . date('d M, Y H:i:s');
            $activity->activity_icon = "user.png";
            $activity->save();
            return redirect()->route('manage_clients')->with('client_updated', "client updated successfully");
        } else {
            $data = new Clients();
            $subscriber = User::find($request->subscriber);
            $this->validate(
                $request,
                [
                    'name' => 'required|string|max:255',
                    'phone' => 'required|unique:users',
                    'email' => 'required|string|email|max:255|unique:users',
                    'nationality' => 'required',
                    // 'passport_no' => 'required',
                    // 'dob' => 'required',
                    'address' => 'required',
                    'country' => 'required|string|max:255',
                    'state' => 'required|string|max:255',
                    'city' => 'required|string|max:255',
                    'pincode' => 'required',
                ]
            );
            $country = Countries::find($request->country);
            $nationality = Countries::find($request->nationality);
            $data->subscriber_id = $subscriber->id;
            $data->user_id = $subscriber->id;
            $data->name = $request['name'];
            $data->phone = $request['phone'];
            $data->email = $request['email'];
            $data->nationality = $nationality->country_name;
            $data->passport_no = $request['passport_no'];
            $data->dob = $request['dob'];
            $data->address = $request['address'];
            $data->alternate_no = $request['alternate_no'];
            $data->status = 'true';
            $data->country = $country->country_name;
            $data->state = $request['state'];
            $data->city = $request['city'];
            $data->pincode = $request['pincode'];
            // print_r($requet->$data);
            // die();
            $data->save();
            $activity = new Activities();
            $activity->subscriber_id = $user->id;
            $activity->user_id = $user->id;
            $activity->user_name = $user->name;
            $activity->activity_name = "New Client Added";
            $activity->activity_detail = "New Client " . $request->name . " added by " . $user->name . " at " . date('d M, Y H:i:s');
            $activity->activity_icon = "user.png";
            $activity->save();
            // $application = new Applications();
            // $application->client_id = $data->id;
            // $application->subscriber_id = $subscriber->id;
            // $application->application_id = job_id();
            // $application->application_category = $subscriber->category;
            // $application->application_subcategory = $subscriber->sub_category;
            // $application->application_name = $request['job_role'];
            // $application->application_country = $request['visa_country'];
            // $application->application_detail = $request['job_detail'];
            // $application->application_program = $request['study_program'];
            // $application->application_status = $request['job_status'];
            // $application->start_date = $request['job_open_date'];
            // $application->end_date = $request['job_completion_date'];
            // $application->save();
            // $activity = new Activities();
            // $activity->subscriber_id = $subscriber->id;
            // $activity->user_id = $user->id;
            // $activity->user_name = $user->name;
            // $activity->activity_name = "New Application Added";
            // $activity->activity_detail = "New Application of " . $request->job_role . " added by " . $user->name . " at " . date('d M, Y H:i:s');
            // $activity->activity_icon = "user.png";
            // $activity->save();
            return redirect()->route('manage_clients')->with('client_added', "client added successfully");
        }
    }

    public function manage_applications()
    {
        $applications = Applications::orderBy('created_at', 'desc')->get();
        $user = Auth::user();
        $page = "applications";
        return view('admin.manage_applications', compact('applications', 'user', 'page'));
    }
    public function manage_reports_applications()
    {
        $startDate = $this->parseReportDate(request()->input('startDate'));
        $endDate = $this->parseReportDate(request()->input('endDate'), true);
        $user = auth()->user();
        $query = new Applications();
        if($user->user_type == 'Subscriber'){
             $query = $query->where('subscriber_id',$user->id);
         }

        $applications =  $query->select([
            'client_id',
            'application_id',
            'application_name',
            'application_country',
            'application_status',
            'start_date',
            'end_date',
            'subscriber_id',
            'created_at',
            'visa_country'
        ])
        ->whereBetween('created_at', [$startDate, $endDate])
        ->orderBy('created_at', 'desc')
        ->get();
        return DataTables::of($applications)
            ->addIndexColumn()
            ->editColumn('application_id', function ($row) {
                // Format the `start_date` for better readability
                return  $row->application_name .'('.$row->application_id.')';
            })
            ->editColumn('subscriber_id', function ($row) {
                // Format the `start_date` for better readability
                return $row->subscriber ? $row->subscriber->name.'('.$row->subscriber_id.')' : '';
            })
            ->editColumn('client_id', function ($row) {
                // Format the `start_date` for better readability
                return $row->client ? $row->client->name.'('.$row->client_id.')' : '';
            })
            ->editColumn('start_date', function ($row) {
                // Format the `start_date` for better readability
                return date('d-m-Y', strtotime($row->start_date));
            })
            ->editColumn('end_date', function ($row) {
                // Format the `end_date`
                return date('d-m-Y', strtotime($row->end_date));
            })
            ->editColumn('created_at', function ($row) {
                // Format the `start_date` for better readability
                return \Carbon\Carbon::parse($row->created_at)->format('d-m-Y'); 
            })

            ->make(true);
    }

    public function new_application()
    {
        $clients = Clients::get();
        $countries = Countries::get();
        $user = Auth::user();
        $page = "applications";
        return view('admin.add_application', compact('clients', 'user', 'page', 'countries'));
    }

    public function application_update($id)
    {
        $application = Applications::find($id);
        $countries = Countries::get();
        $client = Clients::find($application->client_id);
        $subscriber = User::find($client->subscriber_id);
        if ($subscriber->category == "Law Firm") {
            $job_roles = Client_jobs::where('category', '=', $subscriber->category)->get();
        } elseif ($subscriber->category == "Travel Agency") {
            $client_jobs = Client_jobs::where('category', '=', $subscriber->category)->get();
        } else {
            $job_roles = Client_jobs::where('category', '=', $subscriber->category)->where('sub_category', '=', $subscriber->sub_category)->get();
        }
        $user = Auth::user();
        $page = "applications";
        return view('admin.add_application', compact('application', 'job_roles', 'user', 'page', 'countries'));
    }

    public function register_new_application(Request $request)
    {
        $normalizeDate = function ($value) {
            if (!$value) {
                return null;
            }
            try {
                return \Carbon\Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d');
            } catch (\Exception $e) {
                try {
                    return \Carbon\Carbon::createFromFormat('d-m-Y', $value)->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            }
        };
        $resolveVisaCountry = function ($value) {
            if (!$value) {
                return null;
            }
            if (is_numeric($value)) {
                $country = Countries::find($value);
                return $country ? $country->country_name : null;
            }
            return $value;
        };
        function job_id()
        {
            $ch = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $id = "";
            for ($i = 0; $i < 8; $i++) {
                $id = $id . $ch[rand(0, strlen($ch) - 1)];
            }
            return $id;
        }
        $user = Auth::user();
        if ($user) {
            $application = Applications::find($request->id);
            if ($application) {
                $client = Clients::find($request->client);
                $subscriber = User::find($client->subscriber_id);
                $application->application_name = $request['job_role'];
                $application->application_country =  $client->country;
                $application->visa_country = $resolveVisaCountry($request['visa_country']);
                $application->application_detail = $request['job_detail'];
                $application->application_program = $request['study_program'];
                $application->application_status = $request['job_status'];
                $application->start_date = $normalizeDate($request['job_open_date']);
                $application->end_date = $normalizeDate($request['job_completion_date']);
                $application->save();
                $activity = new Activities();
                $activity->subscriber_id = $subscriber->id;
                $activity->user_id = $user->id;
                $activity->user_name = $user->name;
                $activity->activity_name = "Application Updated";
                $activity->activity_detail = "Application of " . $request->job_role . " updated by " . $user->name . " at " . date('d M, Y H:i:s');
                $activity->activity_icon = "user.png";
                $activity->save();
                return redirect()->route('manage_applications')->with('application_updated', "Application Updated successfully");
            } else {
                $client = Clients::find($request->client);
                if ($client) {
                    $subscriber = User::find($client->subscriber_id);
                    $application = new Applications();
                    $application->client_id = $client->id;
                    $application->subscriber_id = $subscriber->id;
                    $application->application_id = job_id();
                    $application->application_name = $request['job_role'];
                    $application->application_category = $subscriber->category;
                    $application->application_subcategory = $subscriber->sub_category;
                    $application->application_country = $client->country;
                    $application->visa_country =  $resolveVisaCountry($request['visa_country']);
                    $application->application_detail = $request['job_detail'];
                    $application->application_program = $request['study_program'];
                    $application->application_status = $request['job_status'];
                    $application->start_date = $normalizeDate($request['job_open_date']);
                    $application->end_date = $normalizeDate($request['job_completion_date']);
                    $application->save();
                    $activity = new Activities();
                    $activity->subscriber_id = $subscriber->id;
                    $activity->user_id = $user->id;
                    $activity->user_name = $user->name;
                    $activity->activity_name = "New Application Added";
                    $activity->activity_detail = "New Application of " . $request->job_role . " added by " . $user->name . " at " . date('d M, Y H:i:s');
                    $activity->activity_icon = "user.png";
                    $activity->save();
                    return redirect()->route('manage_applications')->with('application_added', "Application Added successfully");
                } else {
                    return back();
                }
            }
        } else {
            return redirect()->route('admin');
        }
    }

    public function application_view($id)
    {
        $application = Applications::find($id);
        $user = Auth::user();
        $page = "applications";
        return view('admin.application_view', compact('application', 'user', 'page'));
    }

    public function documents()
    {
        $applications = Applications::get();
        $user = Auth::user();
        $page = "applications";
        $client_docs = Client_Docs::orderBy('created_at', 'desc')->get();
        return view('admin.documents', compact('applications', 'user', 'page', 'client_docs'));
    }

    public function new_document()
    {
        $clients = Clients::get();
        $clients = Clients::get();
        $countries = Countries::get();
        $user = Auth::user();
        $page = "applications";
        return view('admin.add_document', compact('clients', 'user', 'page', 'countries', 'clients'));
    }

    public function document_update($id)
    {
        $document = Client_docs::find($id);
        $clients = Clients::get();
        $application  = Applications::where('application_id', $document->application_id)->first();
        $user = Auth::user();
        $page = "applications";
        return view('admin.add_document', compact('document', 'user', 'page', 'clients', 'application'));
    }

    public function upload_document(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $document = Client_Docs::find($request->id);
            if ($document) {
                $client = Clients::find($request->client_id);
                $application = Applications::find($request->application_id);
                $subscriber = User::find($client->subscriber_id);
                $document->client_id = $request['client_id'];
                $document->application_id = $application->application_id;
                $document->user_id = $subscriber->id;
                $document->doc_type = $request['doc_type'];
                $document->doc_name = $request['doc_name'];
                if ($request->hasFile('doc_file')) {
                    $file = $request->file('doc_file');
                    $extension = $file->getClientOriginalName();
                    $filename = time() . $extension;
                    $file->move('web_assets/users/client' . $document->client_id . '/docs/', $filename);
                    $document->doc_file = $filename;
                }
                $document->save();
                $activity = new Activities();
                $activity->subscriber_id = $subscriber->id;
                $activity->user_id = $user->id;
                $activity->user_name = $user->name;
                $activity->activity_name = "Document Updated";
                $activity->activity_detail = "Document updated by " . $user->name . " at " . date('d M, Y H:i:s');
                $activity->activity_icon = "user.png";
                $activity->save();
                return redirect()->route('documents')->with('document_updated', "Document Updated successfully");
            } else {
                $client = Clients::find($request->client_id);
                if ($client) {
                    $this->validate($request, [
                        'doc_file' => 'required|mimes:jpeg,jpg,png,pdf|max:2048',
                    ]);
                    $document = new Client_Docs();
                    $client = Clients::find($request->client_id);
                    $application = Applications::find($request->application_id);
                    $subscriber = User::find($client->subscriber_id);
                    $document->client_id = $request['client_id'];
                    $document->application_id = $application->application_id;
                    $document->user_id = $subscriber->id;
                    $document->doc_name = $request['doc_name'];
                    $document->doc_type = $request['doc_type'];
                    if ($request->hasFile('doc_file')) {
                        $file = $request->file('doc_file');
                        $extension = $file->getClientOriginalName();
                        $filename = time() . $extension;
                        $file->move('web_assets/users/client' . $document->client_id . '/docs/', $filename);
                        $document->doc_file = $filename;
                    }
                    $document->save();
                    $activity = new Activities();
                    $activity->subscriber_id = $subscriber->id;
                    $activity->user_id = $user->id;
                    $activity->user_name = $user->name;
                    $activity->activity_name = "Document Added";
                    $activity->activity_detail = "New Document added by " . $user->name . " at " . date('d M, Y H:i:s');
                    $activity->activity_icon = "user.png";
                    $activity->save();
                    return redirect()->route('documents')->with('document_added', "Document added successfully");
                } else {
                    return back();
                }
            }
        } else {
            return redirect()->route('admin');
        }
    }

    public function application_management()
    {
        $assignments = Application_assignments::whereHas('client')->whereHas('application')->with('client')->with('application')->orderBy('created_at', 'desc')->get();
        $user = Auth::user();
        $page = "applications";
        return view('admin.application_management', compact('assignments', 'user', 'page'));
    }

    public function application_tracking()
    {
        $user = Auth::user();
        $subscribers = User::where('user_type', '=', 'Subscriber')->get();
        $countries = Countries::get();
        $page = "applications";
        $applications = Applications::orderBy('created_at', 'desc')->where('id', 0)->get();
        return view('admin.application_tracking', compact('applications', 'user', 'page', 'subscribers', 'countries'));
    }

    public function getApplicationData($id)
    {
        // $applications = Applications::where('id', $id)->with('client')->get();
        $applications = Applications::where('id', $id)->with('client.user')->get();


        // Format the data (optional)
        // $data = $applications->map(function ($app, $index) {
        //     return [
        //         'index' => $index + 1,
        //         'status' => $app->application_status,
        //         'start_date' => date("d-m-Y", strtotime($app->start_date)),
        //         'end_date' => $app->end_date ? date("d-m-Y", strtotime($app->end_date)) : '',
        //         'client' => $app->client ? $app->client->name . ' (' . $app->client->id . ')' : '',
        //     ];
        // });

        $data = $applications->map(function ($app, $index) {
            $client = $app->client;
            $user = $client && $client->user ? $client->user : null;

            return [
                'index' => $index + 1,
                'status' => $app->application_status,
                'start_date' => date("d-m-Y", strtotime($app->start_date)),
                'end_date' => $app->end_date ? date("d-m-Y", strtotime($app->end_date)) : '',
                'client' => $client ? $client->name . ' (' . $client->id . ')' : '',
                'user' => $user ? $user->name . ' (' . $user->id . ')' : '',
            ];
        });



        return response()->json($data);
    }
    
    public function getClientsBySubscriber($id)
    {
        $clients = Clients::where('subscriber_id', $id)->get();
        return response()->json($clients);
    }

    public function getApplicationsByClient($clientId)
    {
        $applications = Applications::where('client_id', $clientId)->get(['id', 'application_name']);
        return response()->json($applications);
    }

    public function new_app_assignment()
    {
        $clients = Clients::get();
        $applications = Applications::get();
        $countries = Countries::get();
        $user = Auth::user();
        $page = "applications";
        return view('admin.add_assignment', compact('clients', 'user', 'page', 'countries', 'applications'));
    }

    public function app_assignment_update($id)
    {
        $assignment = Application_assignments::find($id);
        $client = Clients::find($assignment->client_id);
        $applications = Applications::where('client_id', '=', $client->id)->get();
        $advisors = User::where('id', '=', $client->subscriber_id)->orwhere('added_by', '=', $client->subscriber_id)->get();
        $user = Auth::user();
        $page = "applications";
        return view('admin.add_assignment', compact('assignment', 'user', 'advisors', 'page', 'client', 'applications'));
    }

    public function post_app_assignment(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $assignment = Application_assignments::find($request->id);
            if ($assignment) {
                $u = User::find($request->user_id);
                $assignment->client_id = $request['client_id'];
                $assignment->application_id = $request['application_id'];
                $assignment->user_id = $request['user_id'];
                $assignment->subscriber_id = $u->added_by;
                $assignment->user_name = $u->name;
                $assignment->save();
                $app = Applications::where('application_id', '=', $request->application_id)->first();
                $app->assign_to = $u->id;
                $app->save();
                $activity = new Activities();
                $activity->user_id = $user->id;
                $activity->user_name = $user->name;
                $activity->activity_name = "Application Assign Updated";
                $activity->activity_detail = "Application Assign updated by " . $user->name . " at " . date('d M, Y H:i:s');
                $activity->activity_icon = "user.png";
                $activity->save();
                return redirect()->route('application_management')->with('assignment_updated', "Assignment Updated successfully");
            } else {
                $client = Clients::find($request->client_id);
                if ($client) {
                    $assignment = new Application_assignments();
                    $u = User::find($request->user_id);
                    $assignment->client_id = $request['client_id'];
                    $assignment->application_id = $request['application_id'];
                    $assignment->user_id = $request['user_id'];
                    $assignment->subscriber_id = $u->added_by;
                    $assignment->user_name = $u->name;
                    $assignment->save();
                    $app = Applications::where('application_id', '=', $request->application_id)->first();
                    $app->assign_to = $u->id;
                    $app->save();
                    $activity = new Activities();
                    $activity->client_id = $client->id;
                    $activity->user_id = $user->id;
                    $activity->user_name = $user->name;
                    $activity->activity_name = "Assignment Added";
                    $activity->activity_detail = "New Assignment added by " . $user->name . " at " . date('d M, Y H:i:s');
                    $activity->activity_icon = "user.png";
                    $activity->save();
                    return redirect()->route('application_management')->with('assignment_added', "Assignment added successfully");
                } else {
                    return back();
                }
            }
        } else {
            return redirect()->route('admin');
        }
    }

    public function view_user($id = null)
    {
        if (!empty($id)) { //edit the page.
            $vuser  = User::find($id);
            $user = Auth::user();
            if ($vuser->user_type == "Subscriber") {
                $page = "subscriber";
            } else {
                $page = "users";
            }
            return view('admin.view_user', compact('vuser', 'user', 'page'));
        } else { //view the page.
            $user = Banners::get();
            return back();
        }
    }

    public function payments()
    {
        $user = auth()->user();
        if ($user->user_type == "admin") {
            $paymentAR = PaymentARs::where('type','ar')->orderBy('created_at', 'desc')->get();
            $page = "payments";
            return view('admin.payments', compact( 'user', 'page', 'paymentAR'));
        } else {
            return back();
        }
         
        // $user = Auth::user();
        // if ($user) {
        //     $page = "payments";
        //     $payments = Invoices::orderBy('created_at', 'desc')->get();
        //     // $payments = Invoices::where('type', 'inward')->orderBy('created_at', 'desc')->get();
        //     return view('admin.payments', compact('user', 'payments', 'page'));
        // } else {
        //     return back();
        // }
    }

    public function admin_payment_made(){

        $user = $this->check_login();

        // $this->set_timezone();
        if ($user->user_type == "admin") {
            $paymentAP = PaymentARs::where('type','ap')->orderBy('created_at', 'desc')->get();
            $page = "payments";
            return view('admin.payments_made', compact('user', 'page', 'paymentAP'));
        } else {
            return back();
        }
    }
    
    public function manage_report_payments()
    {
        $startDate = $this->parseReportDate(request()->input('startDate'));
        $endDate = $this->parseReportDate(request()->input('endDate'), true);
        $user = auth()->user();
        $query = new PaymentArs ();
        if($user->user_type == 'Subscriber'){
            $query  =  $query->where('subscriber_id',$user->id);
        }
            
        
        $payments = $query->where('type',request()->input('type'))->whereBetween('created_at', [$startDate, $endDate])
                    
                    ->orderBy('created_at','desc')->get();
        


        return DataTables::of($payments)
            ->addIndexColumn()
            ->editColumn('client', function ($row) {
                // Format the `created_at` column
                return $row->client ? $row->client->name.'('.$row->client_id.')' : '';
            })->editColumn('amount_to_pay', function ($row) {
                // Format the `created_at` column
                return ($row->amount - $row->paid_amount);
            })
            ->editColumn('type', function ($row) {
                // Format the `created_at` column
                return  ($row->type == 'ar') ? 'AR' :'AP';
            })
            ->editColumn('payment_date', function ($row) {
                // Format the `created_at` column
                return \Carbon\Carbon::parse($row->payment_date)->format('d-m-Y'); // Example: 18 Dec 2024, 02:15 PM
            })
            ->editColumn('created_at', function ($row) {
                // Format the `created_at` column
                return \Carbon\Carbon::parse($row->created_at)->format('d-m-Y'); // Example: 18 Dec 2024, 02:15 PM
            })
            ->make(true);
    }

    public function manage_invoices()
    {
        $user = Auth::user();
        if ($user) {
            $page = "invoices";
            $invoices = Internal_Invoices::orderBy('created_at', 'desc')->get();
            return view('admin.manage_invoices', compact('user', 'invoices', 'page'));
        } else {
            return back();
        }
    }
    public function manage_reports_invoices()
    {
        $startDate = $this->parseReportDate(request()->input('startDate'));
        $endDate = $this->parseReportDate(request()->input('endDate'), true);
        $user = Auth::user();
        $query = Internal_Invoices::where('type', 'ar');
        if($user->user_type == 'Subscriber'){
            $query =  $query->where('subscriber_id',$user->id);
        }

        $invoices = $query->select([
            'id',
            'to_name',
            'subscriber_id',
            'to_phone',
            'to_email',
            'amount',
            'discount',
            'tax',
            'total',
            'status',
            'due_date',
            'created_at',
            'type'
        ])
        ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        return DataTables::of($invoices)
            ->addIndexColumn()
            ->editColumn('to_name', function ($row) {
                return $row->to_name . '(' . $row->subscriber_id . ')';
            })
            ->editColumn('due_date', function ($row) {
                return date("d-m-Y", strtotime($row->due_date));
            })
            ->editColumn('created_at', function ($row) {
                return date("d-m-Y", strtotime($row->created_at));
            })
            ->addColumn('action', function ($row) {
                return '<a style="background:none; border:none;" href="' . route('view_invoice', $row->id) . '" class="m-0 p-0"><i class="fa-solid fa-eye btn p-1 text-info" style="font-size:14px;"></i></a>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function manage_reports_invoices_ap()
    {
        $startDate = $this->parseReportDate(request()->input('startDate'));
        $endDate = $this->parseReportDate(request()->input('endDate'), true);
        $user = Auth::user();
        $query = Internal_Invoices::where('type', 'ap');
        if($user->user_type == 'Subscriber'){
            $query =  $query->where('subscriber_id',$user->id);
        }

        $invoices = $query->select([
            'id',
            'to_name',
            'subscriber_id',
            'to_phone',
            'to_email',
            'amount',
            'discount',
            'tax',
            'total',
            'status',
            'due_date',
            'created_at',
            'type'
        ])
        ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        return DataTables::of($invoices)
            ->addIndexColumn()
            ->editColumn('to_name', function ($row) {
                return $row->to_name . '(' . $row->subscriber_id . ')';
            })
            ->editColumn('due_date', function ($row) {
                return date("d-m-Y", strtotime($row->due_date));
            })
            ->editColumn('created_at', function ($row) {
                return date("d-m-Y", strtotime($row->created_at));
            })
            ->addColumn('action', function ($row) {
                return '<a style="background:none; border:none;" href="' . route('view_invoice', $row->id) . '" class="m-0 p-0"><i class="fa-solid fa-eye btn p-1 text-info" style="font-size:14px;"></i></a>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function admin_new_invoice()
    {
        $user = Auth::user();
        $subscribers = User::where('user_type', '=', 'Subscriber')->get();
        $countries = Countries::get();
        $page = "invoices";
        return view('admin.add_invoice', compact('subscribers', 'user', 'page', 'countries'));
    }

    public function admin_new_invoice_post(Request $request)
    {
        function invoice_id()
        {
            $ch = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $id = "";
            for ($i = 0; $i < 8; $i++) {
                $id = $id . $ch[rand(0, strlen($ch) - 1)];
            }
            return $id;
        }
        function invoice_token()
        {
            $ch = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $token = "";
            for ($i = 0; $i < 20; $i++) {
                $token = $token . $ch[rand(0, strlen($ch) - 1)];
            }
            if (Internal_Invoices::where('token', '=', $token)->first()) {
                return invoice_token();
            }
            return $token;
        }
        $user = Auth::user();
        $inv_setting = Invoice_settings::where('user_id', $user->id)->first();
        if ($user) {
            if ($request->subscriber) {
                $subs = User::find($request->subscriber);
                $invoice = new Internal_Invoices();
                $invoice->invoice_no = invoice_id();
                $invoice->subscriber_id = $subs->id;
                // $invoice->user_id = $user->id;
                $invoice->name = $user->organization;
                $invoice->email = $user->email;
                $invoice->phone = $user->phone;
                $invoice->country = $user->country;
                $invoice->state = $user->state;
                $invoice->city = $user->city;
                $invoice->pincode = $user->pincode;
                $invoice->address = $user->address_line;
                $invoice->logo = $user->organization_logo;
                $invoice->to_name = $subs->name;
                $invoice->to_email = $subs->email;
                $invoice->to_phone = $subs->phone;
                $invoice->to_country = $subs->country;
                $invoice->to_state = $subs->state;
                $invoice->to_city = $subs->city;
                $invoice->to_pincode = $subs->pincode;
                $invoice->to_address = $subs->address_line;
                $invoice->detail = $request['detail'];
                $invoice->amount = $request['amount'];
                $discountPercent = max(0, min(100, (float) ($inv_setting->discount ?? 0)));
                $taxPercent = max(0, min(100, (float) ($inv_setting->tax ?? 0)));
                $amount = (float) $request['amount'];
                $subtotal = $amount - ($amount * ($discountPercent / 100));

                $invoice->discount = $discountPercent;
                $invoice->tax = $taxPercent;
                $invoice->total = max(0, $subtotal + ($subtotal * ($taxPercent / 100)));
                $invoice->status = $request['status'];
                $invoice->due_date = preg_match('/^\d{2}-\d{2}-\d{4}$/', (string) $request['due_date'])
                    ? \Carbon\Carbon::createFromFormat('d-m-Y', $request['due_date'])->format('Y-m-d')
                    : $request['due_date'];
                $invoice->token = invoice_token();
                $invoice->save();
                $activity = new Activities();
                $activity->subscriber_id = $user->id;
                $activity->user_id = $user->id;
                $activity->user_name = $user->name;
                $activity->activity_name = "Invoice Generated";
                $activity->activity_detail = "Invoice generated by " . $user->name . " at" . date('d M, Y H:i:s');
                $activity->activity_icon = "invoice.jpg";
                $activity->save();

                $maildata = new \stdClass();
                $maildata->name = $subs['name'];
                $maildata->email = $user->email;
                $maildata->from_email = $user->email;
                $maildata->to_email = $subs->email;
                $maildata->company_name = $user->organization ?? $user->name;
                $maildata->display_from_email = $user->email;
                $maildata->logo_path = 'web_assets/users/user' . $user->id . '/' . $invoice->logo;
                $maildata->detail = $invoice->detail;
                $maildata->amount = $invoice->amount;
                $maildata->discount = $invoice->discount;
                $maildata->tax = $invoice->tax;
                $maildata->total = $invoice->total;
                $maildata->currency = $user->currency ?? 'Rs.';
                $maildata->status = $invoice->status;
                $maildata->invoice_no = $invoice->invoice_no;
                $maildata->invoice_date = $invoice->created_at;
                $maildata->due_date = $invoice->due_date;
                $maildata->invoice_id = $invoice->id;
                $maildata->token = $invoice->token;
                $maildata->message = "You have new invoice from " . ($user->organization ?? 'Adwiseri') . " for " . ($user->currency ?? 'Rs.') . " " . number_format($invoice->total, 2) . ".";
                $maildata->from_name = $user->organization ?? $user->name ?? 'Subscriber';
                $maildata->from_email = $user->email;
                $maildata->reply_to_email = $user->email;
                $maildata->reply_to_name = $user->organization ?? $user->name ?? 'Subscriber';
                Mail::to($subs->email)->send(new Invoicemail($maildata));
                if (Mail::failures()) {
                    echo 'Sorry! Please try again latter';
                } else {
                    echo 'Success';
                }
                return redirect()->route('manage_invoices')->with('invoice_generated', 'Invoice created Successfully.');
            }
        } else {
            return redirect()->route('login');
        }
    }

    public function invoice_detail($id)
    {
        $user = Auth::user();
        if ($id) {
            $invoice = Internal_Invoices::find($id);
            $u = User::where('email', '=', $invoice->email)->first();
            if ($u) {
                $invoiceSetting = Invoice_settings::where('user_id', $u->id)->first();
                if ($invoiceSetting) {
                    $invoice->discount = $invoiceSetting->discount;
                    $invoice->tax = $invoiceSetting->tax;
                    $invoice->paymenyt_link = $invoiceSetting->payment_link;
                }
                $page = "invoices";
                return view('admin.invoice_detail', compact('user', 'invoice', 'page', 'u', 'invoiceSetting'));
            } else {
                return back()->with('nouser', 'invoice user no longer exists');
            }
        } else {
            return back();
        }
    }

    public function print_invoice_detail($id)
    {
        $user = Auth::user();
        $page = "invoices";
        $invoice = Internal_Invoices::find($id);
        $u = User::where('email', '=', $invoice->email)->first();
        $invoiceSetting = Invoice_settings::where('user_id', $u->id)->first();
                if ($invoiceSetting) {
                    $invoice->discount = $invoiceSetting->discount;
                    $invoice->tax = $invoiceSetting->tax;
                    $invoice->paymenyt_link = $invoiceSetting->payment_link;
                }
        return view('admin.print_invoice_detail', compact('user', 'page', 'invoice', 'u', 'invoiceSetting'));
    }

    public function admin_wallet()
    {
        $user = Auth::user();
        if ($user) {
            $page = "wallet";
            $referrals = Referrals::orderBy('created_at', 'desc')
                ->get();

            return view('admin.wallet', compact('user', 'referrals', 'page'));
        } else {
            return back();
        }
    }
    public function manage_report_wallet()
    {
        $startDate = $this->parseReportDate(request()->input('startDate'));
        $endDate = $this->parseReportDate(request()->input('endDate'), true);
        $user = auth()->user();
        $query = new Referrals ();
        if($user->user_type == 'Subscriber'){
            $query =  $query->where('userid',$user->id);
        }
        $referrals = $query->whereBetween('created_at', [$startDate, $endDate])->where('debit_amount', '=', null)->orderBy('created_at', 'desc')->get();
        return DataTables::of($referrals)
        ->addIndexColumn()
        ->editColumn('walletId', function ($row) {
            return $row->id;
        })
        ->editColumn('user_name', function ($row) {
            // if (strlen($row->user_name) > 15) {
            //     return  substr($row->user_name, 0, 15) . '...';
            // } else {
                return $row->user_name;
            // }
        })

        ->addColumn('finalamount', function ($row) {

            if (!empty($row->amount_added)) {
                return $row->amount_added;
            } elseif (!empty($row->debit_amount)) {
                return $row->debit_amount;
            }
            return 0;
        })
        ->addColumn('TransactionType', function ($row) {
            $wallet_balance = round($row->wallet_balance,2) ?? 0;
            $previous_balance = round($row->previous_balance,2) ?? 0;
            $result ='';
            if($wallet_balance > 0 && $wallet_balance > $previous_balance){
                $result =  '+'.round(($wallet_balance - $previous_balance),2);
             }elseif ($previous_balance > 0 && $wallet_balance < $previous_balance){
             $result = '-'.round(($previous_balance - $wallet_balance),2);
             }else{
             $result ='0';
            }
            return $result;
        })
         ->addColumn('type', function ($row) {
            $displayText = '';
            switch ($row->type) {
                case 'cashback':
                    $displayText = 'Cashback Credit';
                    break;
                case 'one_off':
                    $displayText = 'One Time Credit';
                    break;
                case 'double_term':
                    $displayText = 'Double Term Discount';
                    break;
                default:
                    $displayText = $row->type;
            }
            return $displayText;

        })
        ->editColumn('created_at', function ($row) {
            return date("d-m-Y", strtotime($row->created_at));
        })
        ->make(true);
    }

    public function admin_referral()
    {
        $user = Auth::user();
        if ($user) {
            $page = "wallet";
            // $referrals = Referrals::where('debit_amount', '=', null)->whereNotIn('type', ['one_off', 'double_term', 'cashback'])->orderBy('created_at', 'desc')->get();
            $referrals = Referrals::whereNotIn('type', ['one_off', 'double_term', 'cashback'])->where('type','Referral Commission')->orderBy('created_at', 'desc')->get();
            return view('admin.referrals', compact('user', 'referrals', 'page'));
        } else {
            return back();
        }
    }

    public function manage_report_referrals()
    {
        $startDate = $this->parseReportDate(request()->input('startDate'));
        $endDate = $this->parseReportDate(request()->input('endDate'), true);
        //  $referrals = Referrals::where('debit_amount', '=', null)->whereBetween('created_at', [$startDate, $endDate])->whereNotIn('type', ['one_off', 'double_term', 'cashback'])->orderBy('created_at', 'desc')->get();
        $user = auth()->user();
        $query = Referrals::join('users', 'referrals.userid', '=', 'users.id')
                            ->whereBetween('users.created_at', [$startDate, $endDate]) // Filter by user creation date
                            ->where('users.user_type', 'Subscriber') // Ensure user_type is 'Subscriber'
                            ->whereNotNull('users.referral_code') // Ensure referral_code exists
                            ->whereNull('referrals.debit_amount') // Ensure debit_amount is null
                            ->orderBy('referrals.created_at', 'desc') // Order by referral creation date
                            ->select('referrals.*'); // Select all columns from referrals
        if($user->user_type == 'Subscriber'){
            $query = $query->where('users.referral_code', $user->referral);// Apply referral code filter for Subscriber
                       
        }
        $referrals = $query->where('referrals.type', 'Referral Commission') // Apply specific condition for Subscriber
        ->get();
        
        return  DataTables::of($referrals)
            ->addIndexColumn()
            ->editColumn('referral_by', function ($row) {
                $user = User::where('referral',$row->referral_code)->first();
                return $user ? $user->name.'('.$user->id.')' :'';
            })
            ->editColumn('referral_to', function ($row) {
                return  $row->user ?  $row->user->name.'('.$row->userid.')' :'';
            })
            ->editColumn('membership', function ($row) {
                return  $row->user ?  $row->user->membership  :'';
            })
            ->editColumn('dos', function ($row) {
                return    (\Carbon\Carbon::parse($row->user->membership_start_date)->diffInYears(\Carbon\Carbon::parse($row->user->membership_expiry_date)) > 0
                                    ? \Carbon\Carbon::parse($row->user->membership_start_date)->diffInYears(\Carbon\Carbon::parse($row->user->membership_expiry_date)) . ' year' . (\Carbon\Carbon::parse($row->user->membership_start_date)->diffInYears(\Carbon\Carbon::parse($row->user->membership_expiry_date)) > 1 ? 's' : '')
                                    : '');


            })
            ->editColumn('total_amount', function ($row) {
                return $row->total_amount;
            })
            ->editColumn('amount_added', function ($row) {
                return $row->amount_added;
            })


            ->editColumn('created_at', function ($row) {
                return date("d-m-Y", strtotime($row->created_at));
            })

            ->rawColumns(['status', 'action'])
            ->make(true);
    }
    // public function reports()
    // {
    //     $user = Auth::user();
    //     $applications = Applications::get();
    //     $visa_categories = Subscriber_Sub_Categories::where('category_name','=','Visas & Immigration Advisory')->get();
    //     $law_categories = Subscriber_Sub_Categories::where('category_name','=','Law Firm')->get();
    //     $travel_categories = Countries::get();
    //     $total_visa_categ = array();
    //     foreach($visa_categories as $vcategory){
    //         $categ = $vcategory->sub_category_name;
    //         $categ_app = 0;
    //         foreach($applications as $app){
    //             if($categ == $app->application_subcategory){
    //                 $categ_app += 1;
    //             }
    //         }
    //         $total_visa_categ[$categ] = $categ_app;
    //     }
    //     $total_law_categ = array();
    //     foreach($law_categories as $lcategory){
    //         $categ = $lcategory->sub_category_name;
    //         $categ_app = 0;
    //         foreach($applications as $app){
    //             if($categ == $app->application_subcategory){
    //                 $categ_app += 1;
    //             }
    //         }
    //         $total_law_categ[$categ] = $categ_app;
    //     }
    //     $total_travel_categ = array();
    //     foreach($travel_categories as $tcategory){
    //         $categ = $tcategory->country_name;
    //         $categ_app = 0;
    //         foreach($applications as $app){
    //             if($categ == $app->application_subcategory){
    //                 $categ_app += 1;
    //             }
    //         }
    //         $total_travel_categ[$categ] = $categ_app;
    //     }
    //     $visa = Applications::where('application_category','=','Visas & Immigration Advisory')->get();
    //     $law = Applications::where('application_category','=','Law Firm')->get();
    //     $travel = Applications::where('application_category','=','Travel Agency')->get();
    //     $internal_invoices = Internal_Invoices::get();
    //     $internal_total = 0;
    //     foreach($internal_invoices as $inv){
    //         $internal_total += $inv->total;
    //     }
    //     $unpaid = Internal_Invoices::where('status','=','UnPaid')->get();
    //     $unpaid_total = 0;
    //     foreach($unpaid as $inv){
    //         $unpaid_total += $inv->total;
    //     }
    //     $invoices = Invoices::get();
    //     $total = 0;
    //     foreach($invoices as $inv){
    //         $total += $inv->total;
    //     }
    //     $total_invoices = count($internal_invoices) + count($invoices);
    //     $total_paid = $total_invoices - count($unpaid);
    //     $total_unpaid = count($unpaid);
    //     $total_amt = $internal_total + $total;
    //     $paid_total = $total_amt - $unpaid_total;
    //     $page = "reports";
    //     return view('admin.reports',compact('user','total_visa_categ','total_law_categ','total_travel_categ','visa','law','travel','page','applications','total_invoices','total_paid','total_unpaid','total_amt','paid_total','unpaid_total'));
    // }

    public function reports()
    {


        $user = $this->check_login();


        if ($user->user_type != "admin" && (new DateTime($user->membership_expiry_date)) < (new DateTime("now")) && $user->user_type != 'admin') {

            return redirect()->route('user_membership')->with("price_plan_expiry", "Please renew or upgrade price plan.");
        }
        $this->set_timezone();
        if ($user->user_type == "admin") {
            $subscriber = $user;
        } else {
            $subscriber = User::find($user->added_by);
            if (empty($subscriber)) {
                $subscriber = $user;
            }
        }
        $activity = new Activities();
        $activity->user_id = $user->id;
        $activity->user_name = $user->name;
        $activity->activity_name = "Fetched Reports";
        $activity->activity_detail = "Reports Fetched by " . $user->name . " at " . date('d M, Y H:i:s');
        $activity->activity_icon = "user.png";
        $activity->save();


        if ($subscriber->category == "Law Firm") {
            $client_jobs = Client_jobs::where('category', '=', $subscriber->category)->get();
        } elseif ($subscriber->category == "Travel Agency") {
            $client_jobs = Client_jobs::where('category', '=', $subscriber->category)->get();
        } else {
            $client_jobs = Client_jobs::where('category', '=', $subscriber->category)->where('sub_category', '=', $subscriber->sub_category)->get();
        }
        $visa_categories = Subscriber_Sub_Categories::where('category_name', '=', 'Visas & Immigration Advisory')->get();
        $law_categories = Subscriber_Sub_Categories::where('category_name', '=', 'Law Firm')->get();
        $countries = Countries::get();
        $total_apps = array();
        $applications = Applications::where('subscriber_id', '=', $subscriber->id)->get();

        foreach ($client_jobs as $job) {
            $categ = $job->job;
            $categ_app = 0;
            foreach ($applications as $app) {
                if ($categ == $app->application_name) {
                    $categ_app += 1;
                }
            }
            $total_apps[$categ] = $categ_app;
        }
        $internal_invoices = Internal_Invoices::where('subscriber_id', '=', $subscriber->id)->get();
        $internal_total = 0;
        foreach ($internal_invoices as $inv) {
            $internal_total += $inv->total;
        }
        $unpaid = Internal_Invoices::where('subscriber_id', '=', $subscriber->id)->where('status', '=', 'UnPaid')->get();
        $unpaid_total = 0;
        foreach ($unpaid as $inv) {
            $unpaid_total += $inv->total;
        }
        $invoices = Invoices::where('user_id', '=', $subscriber->id)->get();
        $total = 0;
        foreach ($invoices as $inv) {
            $total += $inv->total;
        }
        $total_invoices = count($internal_invoices);
        $total_paid = $total_invoices - count($unpaid);
        $total_unpaid = count($unpaid);
        $total_amt = $internal_total;
        $paid_total = $total_amt - $unpaid_total;
        $page = "reports";
        $support = User::where('designation', 'Support Team Member')->get();

        if ($user->user_type == "admin") {

            return view('admin.reportsForAdmin', compact('support', 'countries', 'user', 'total_apps', 'page', 'applications', 'total_invoices', 'total_paid', 'total_unpaid', 'total_amt', 'paid_total', 'unpaid_total'));
        } elseif ($user->membership == "Advisory+" || $user->membership == "Adwiseri+" || $user->membership == "Enterprise") {

            return view('admin.reportsForUsers', compact('support', 'countries', 'user', 'total_apps', 'page', 'applications', 'total_invoices', 'total_paid', 'total_unpaid', 'total_amt', 'paid_total', 'unpaid_total'));
        }
    }

    public function manage_support()
    {
        $user = Auth::user();
       
        $query = new  Tickets();
        if(request()->has('startDate') &&  request()->has('endDate')){
            $startDate = $this->parseReportDate(request()->input('startDate'));
            $endDate = $this->parseReportDate(request()->input('endDate'), true);
            $query = $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        if ($user) {
            if($user->is_support == 1){
                $tickets =  $query->orderBy('created_at', 'desc')->where('served_by',auth()->id())->get();
            }else{
                $tickets =  $query->orderBy('created_at', 'desc')->get();
            }
            $supportStaff = User::where('user_type', 'admin')->where('is_support', 1)->get();
            $page = "support";
            if (request()->ajax()) {


                return DataTables::of($tickets)
                    ->addIndexColumn()
                    ->editColumn('subscriber', function ($row) {
                       return $row->subscriber ? $row->subscriber->name.'('.$row->subscriber_id.')' :'';
                    })
                    ->editColumn('client', function ($row) {
                        return $row->client ? $row->client->name.'('.$row->client_id.')' :'';
                     })
                    ->editColumn('status', function ($row) {
                        $html = "";
                        if ($row->status == 'Open') {
                            $html .= '<a style="background:green;border-color:green;" href="' . route('update_query_status', $row->id) . '" class="p-0 px-1">Open</a>';
                        } else {
                            $html .= '<a style="background:red;border-color:red;" href="' . route('update_query_status', $row->id) . '" class="p-0 px-1">Closed</a>';
                        }
                        return $html;
                    })
                    ->editColumn('issue', function ($row) {
                        $text = htmlspecialchars($row->issue);
                        $words = explode(' ', $text);
                        $truncated = implode(' ', array_slice($words, 0, 25)); // First 25 words

                        return '<div class="message-tooltip" data-full-text="' . htmlspecialchars($text) . '">
                                    <span class="hover-expand">' . $truncated . '...</span>
                                </div>';
                    })
                    ->editColumn('created_at', function ($row) {
                        return date("d-m-Y H:i:s", strtotime($row->created_at));
                    })
                    ->addColumn('attachment', function ($row) {
                        if (!$row->attachment) {
                            return 'N/A';
                        }

                        $attachmentPath = 'web_assets/users/ticket_images/' . $row->attachment;
                        if (!file_exists($attachmentPath)) {
                            return 'File missing';
                        }

                        return '<a href="' . asset($attachmentPath) . '" target="_blank" download="' . e($row->attachment) . '">View/Download</a>';
                    })
                    ->addColumn('action', function ($row) {
                        return '<a style="background:none; border:none;" onclick="window.location.href = "' . route('view_query', $row->id) . '"";" class="m-0 p-0"><i class="fa-solid fa-eye btn p-1 text-info" style="font-size:14px;"></i></a>';
                    })
                    ->rawColumns(['status', 'action', 'issue', 'attachment'])
                    ->make(true);
            }
            return view('admin.manage_support', compact('user', 'tickets', 'page','supportStaff'));
        } else {
            return redirect()->route('admin');
        }
    }
    public function affiliates_records()
    {
        $startDate = $this->parseReportDate(request()->input('startDate'));
        $endDate = $this->parseReportDate(request()->input('endDate'), true);
            // $referral = Referrals::selectRaw('COUNT(userid) as Referred, referral_code, MAX(wallet_balance) as balance')->whereBetween('created_at',[$startDate,$endDate])
            // ->groupBy('referral_code');
            $affiliates =  User::where('user_type', 'Affiliate')
            // ->withSum('affiliateTotalCommission as total_commission', 'amount_added')
            // ->having('total_commission', '>', 0)
            ->where('wallet', '>', 0)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

            return DataTables::of( $affiliates)
            ->addIndexColumn()
            ->editColumn('type', function ($row) {
                return $row->getAffiliate ? $row->getAffiliate->type : '';
            })
            ->make(true);
    }
    public function manage_faq()
    {
        $user = Auth::user();
        if ($user) {
            $faqs = Faq::get();
            $page = "support";
            return view('admin.manage_faq', compact('user', 'faqs', 'page'));
        } else {
            return redirect()->route('admin');
        }
    }

    public function add_faq()
    {
        $user = Auth::user();
        if ($user) {
            $page = "support";
            return view('admin.add_faq', compact('user', 'page'));
        } else {
            return redirect()->route('admin');
        }
    }

    public function update_faq($id)
    {
        $user = Auth::user();
        if ($user) {
            $faq = Faq::find($id);
            $page = "support";
            return view('admin.add_faq', compact('user', 'page', 'faq'));
        } else {
            return redirect()->route('admin');
        }
    }

    public function register_faq(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $faq = Faq::find($request->id);
            if ($faq) {
                $faq->question = $request['question'];
                $faq->answer = $request['answer'];
                $faq->save();
                $activity = new Activities();
                $activity->user_id = $user->id;
                $activity->user_name = $user->name;
                $activity->activity_name = "FAQ Updated";
                $activity->activity_detail = "FAQ updated by " . $user->name . " at " . date('d M, Y H:i:s');
                $activity->activity_icon = "user.png";
                $activity->save();
                return redirect()->route('manage_faq')->with('faq_updated', "FAQ Updated successfully");
            } else {
                $faq = new Faq();
                $this->validate($request, [
                    'question' => 'required',
                    'answer' => 'required',
                ]);
                $faq->question = $request['question'];
                $faq->answer = $request['answer'];
                $faq->save();
                $activity = new Activities();
                $activity->user_id = $user->id;
                $activity->user_name = $user->name;
                $activity->activity_name = "New FAQ Added";
                $activity->activity_detail = "New FAQ added by " . $user->name . " at " . date('d M, Y H:i:s');
                $activity->activity_icon = "user.png";
                $activity->save();
                return redirect()->route('manage_faq')->with('faq_added', "FAQ Added successfully");
            }
        } else {
            return redirect()->route('admin');
        }
    }

    public function activity_log()
    {
        $user = Auth::user();

        $query = new  Activities();
        if(request()->has('startDate') &&  request()->has('endDate')){
            $startDate = $this->parseReportDate(request()->input('startDate'));
            $endDate = $this->parseReportDate(request()->input('endDate'), true);
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
       
        if ($user) {
            $page = "activity_log";
            $users = User::get();
            $activities = $query->orderBy('created_at', 'desc')->get();
            if (request()->ajax()) {

                return DataTables::of($activities)
                    ->addIndexColumn()
                    ->editColumn('created_at', function ($row) {
                        return date("d-m-Y ", strtotime($row->created_at));
                    })
                    ->make(true);
            }
            return view('admin.activity_log', compact('user', 'activities', 'page', 'users'));
        } else {
            return back();
        }
    }

    public function view_client($id = null)
    {
        if (!empty($id)) { //edit the page.
            $client  = Clients::find($id);
            $user = Auth::user();
            $page = "clients";
            return view('admin.view_client', compact('client', 'user', 'page'));
        } else { //view the page.
            return back();
        }
    }

    public function manage_contactus()
    {
        $contact = Contactus::first();
        $user = Auth::user();
        $page = "contactus";
        return view('admin.manage_contactus', compact('user', 'contact', 'page'));
    }

    public function update_contactus(request $request)
    {
        // print_r($request->all());
        //    exit();
        $user = Auth::user();

        if ($user) {
            $contact = Contactus::find($request->id);
            if ($contact) {
                $contact_us = Contactus::find($request->id);
                if (isset($request->contact_details)) {
                    $contact_us->contact_no = $request['contact_no'];
                    $contact_us->alternate_no = $request['alternate_no'];
                    $contact_us->location = $request['location'];
                    $contact_us->email = $request['email'];
                    $contact_us->website = $request['website'];
                    $contact_us->save();
                    $activity = new Activities();
                    $activity->user_id = $user->id;
                    $activity->user_name = $user->name;
                    $activity->activity_name = "Contact Us Updated";
                    $activity->activity_detail = "Contact Us updated by " . $user->name . " at " . date('d M, Y H:i:s');
                    $activity->activity_icon = "user.png";
                    $activity->save();
                    return back()->with('success', 'Contact Us Updated Successfully!');
                } elseif (isset($request->banner)) {
                    if ($request->hasFile('banner')) {
                        $file = $request->file('banner');
                        $extension = $file->getClientOriginalName();
                        $filename = time() . $extension;
                        $file->move('admin_assets/contactus/', $filename);
                        $contact_us->banner = $filename;
                    }
                    $contact_us->save();
                    $activity = new Activities();
                    $activity->user_id = $user->id;
                    $activity->user_name = $user->name;
                    $activity->activity_name = "Contact Us Updated";
                    $activity->activity_detail = "Contact Us Banner updated by " . $user->name . " at " . date('d M, Y H:i:s');
                    $activity->activity_icon = "user.png";
                    $activity->save();
                    return back()->with('success', 'Banner Updated Successfully!');
                }
            } else {
                return back();
            }
        } else {
            return redirect()->route('login');
        }
    }

    public function manage_membership()
    {
        $user = Auth::user();
        $page = "membership";
        $memberships = Membership::get();
        return view('admin.manage_membership', compact('user', 'page', 'memberships'));
    }

    public function add_membership()
    {
        $user = Auth::user();
        $page = "membership";
        return view('admin.add_membership', compact('user', 'page'));
    }

    public function post_membership(request $request)
    {
        // print_r($request->all());
        //    exit();
        $user = Auth::user();
        if (isset($request->id)) {
            $data = Membership::find($request->id);
            $data->plan_name = $request['plan_name'];
            $data->data_limit = $request['data_limit'];
            $data->client_limit = $request['client_limit'];
            $data->reports = $request['reports'];
            $data->analytics = $request['analytics'];
            $data->no_of_users = $request['no_of_users'];
            $data->no_of_branches = $request['no_of_branches'];
            $data->price_per_year = $request['price_per_year'];
            $data->validity = $request['validity'];
            $data->messaging = $request['messaging'];
            $data->invoicing = $request['invoicing'];
            $data->multi_device_support = $request['multi_device_support'];
            $data->secure_environment = $request['secure_environment'];
            $data->multi_currency_support = $request['multi_currency_support'];
            // print_r($requet->$data);
            // die();
            $data->save();
            $activity = new Activities();
            $activity->user_id = $user->id;
            $activity->user_name = $user->name;
            $activity->activity_name = "Price Plan Updated";
            $activity->activity_detail = "Price Plan updated by " . $user->name . " at " . date('d M, Y H:i:s');
            $activity->activity_icon = "user.png";
            $activity->save();
            return back()->with('success', 'Membership Plan added Successfully');
        } else {
            $data = new Membership();
            $this->validate(
                $request,
                [
                    'plan_name' => 'required|string|max:255',
                    'data_limit' => 'required|string|max:255',
                    'client_limit' => 'required|numeric',
                    'reports' => 'required|string|max:255',
                    'analytics' => 'required|string|max:255',
                    'no_of_users' => 'required|numeric',
                    'no_of_branches' => 'required|numeric',
                    'price_per_year' => 'required|numeric',
                    'validity' => 'required|numeric',
                    'messaging' => 'required|string|max:255',
                    'invoicing' => 'required|string|max:255',
                    'multi_device_support' => 'required|string|max:255',
                    'secure_environment' => 'required|string|max:255',
                    'multi_currency_support' => 'required|string|max:255',
                ]
            );
            $data->plan_name = $request['plan_name'];
            $data->data_limit = $request['data_limit'];
            $data->client_limit = $request['client_limit'];
            $data->reports = $request['reports'];
            $data->analytics = $request['analytics'];
            $data->no_of_users = $request['no_of_users'];
            $data->no_of_branches = $request['no_of_branches'];
            $data->price_per_year = $request['price_per_year'];
            $data->validity = $request['validity'];
            $data->messaging = $request['messaging'];
            $data->invoicing = $request['invoicing'];
            $data->multi_device_support = $request['multi_device_support'];
            $data->secure_environment = $request['secure_environment'];
            $data->multi_currency_support = $request['multi_currency_support'];
            // print_r($requet->$data);
            // die();
            $data->save();
            $activity = new Activities();
            $activity->user_id = $user->id;
            $activity->user_name = $user->name;
            $activity->activity_name = "Price Plan Added";
            $activity->activity_detail = "New Price Plan added by " . $user->name . " at " . date('d M, Y H:i:s');
            $activity->activity_icon = "user.png";
            $activity->save();
            return back()->with('success', 'Membership Plan added Successfully');
        }
    }

    public function membership_plan($id = null)
    {
        if (!empty($id)) { //edit the page.
            $plan  = Membership::find($id);
            $user = Auth::user();
            $page = "membership";
            return view('admin.membership_plan', compact('plan', 'user', 'page'));
        } else { //view the page.
            return back();
        }
    }

    public function banners(Request $request)
    {
        $user = Auth::user();
        $banner = Banners::first();
        if ($request->hasFile('membership')) {
            $file = $request->file('membership');
            $extension = $file->getClientOriginalName();
            $filename = time() . $extension;
            $file->move("admin_assets/banners/membership/", $filename);
            $banner->membership = $filename;
        }
        if ($request->hasFile('about_advisori')) {
            $file = $request->file('about_advisori');
            $extension = $file->getClientOriginalName();
            $filename = time() . $extension;
            $file->move("admin_assets/banners/about_advisori/", $filename);
            $banner->about_advisori = $filename;
        }
        if ($request->hasFile('features')) {
            $file = $request->file('features');
            $extension = $file->getClientOriginalName();
            $filename = time() . $extension;
            $file->move("admin_assets/banners/features/", $filename);
            $banner->features = $filename;
        }
        $banner->save();
        return back();
    }

    public function manage_features()
    {
        $user = Auth::user();
        $page = "features";
        $features = Features::get();
        return view('admin.manage_features', compact('user', 'page', 'features'));
    }

    public function add_feature()
    {
        $user = Auth::user();
        $page = "features";
        return view('admin.add_feature', compact('user', 'page'));
    }

    public function post_feature(request $request)
    {
        // print_r($request->all());
        //    exit();
        $user = Auth::user();
        if (isset($request->id)) {
            $data = Features::find($request->id);
            $this->validate(
                $request,
                [
                    'name' => 'required|string|max:255',
                    'content' => 'required',
                    'icon' => 'image|mimes:jpg,png,jpeg,gif,svg|max:2048',
                ]
            );
            $data->name = $request['name'];
            $data->content = $request['content'];
            if ($request->hasFile('icon')) {
                $file = $request->file('icon');
                $extension = $file->getClientOriginalName();
                $filename = time() . $extension;
                $file->move("admin_assets/features/icon/", $filename);
                $data->icon = $filename;
            }
            // print_r($requet->$data);
            // die();
            $data->save();
            $activity = new Activities();
            $activity->user_id = $user->id;
            $activity->user_name = $user->name;
            $activity->activity_name = "Features Updated";
            $activity->activity_detail = "Features updated by " . $user->name . " at " . date('d M, Y H:i:s');
            $activity->activity_icon = "user.png";
            $activity->save();
            return back()->with('success', 'Feature Updated Successfully');
        } else {
            $data = new Features();
            $this->validate(
                $request,
                [
                    'name' => 'required|string|max:255',
                    'content' => 'required',
                    'icon' => 'required|image|mimes:jpg,png,jpeg,gif,svg|max:2048',
                ]
            );
            $data->name = $request['name'];
            $data->content = $request['content'];
            if ($request->hasFile('icon')) {
                $file = $request->file('icon');
                $extension = $file->getClientOriginalName();
                $filename = time() . $extension;
                $file->move("admin_assets/features/icon/", $filename);
                $data->icon = $filename;
            }
            // print_r($requet->$data);
            // die();
            $data->save();
            $activity = new Activities();
            $activity->user_id = $user->id;
            $activity->user_name = $user->name;
            $activity->activity_name = "Feature Added";
            $activity->activity_detail = "New Feature added by " . $user->name . " at " . date('d M, Y H:i:s');
            $activity->activity_icon = "user.png";
            $activity->save();
            return back()->with('success', 'Feature Added Successfully');
        }
    }

    public function view_feature($id = null)
    {
        if (!empty($id)) { //edit the page.
            $feature  = Features::find($id);
            $user = Auth::user();
            $page = "features";
            return view('admin.view_feature', compact('feature', 'user', 'page'));
        } else { //view the page.
            return back();
        }
    }

    public function manage_about_adwiseri()
    {
        $user = Auth::user();
        $page = "about_adwiseri";
        $about_adwiseri = About_Advisori::first();
        return view('admin.manage_about_advisori', compact('user', 'page', 'about_adwiseri'));
    }

    public function update_about_adwiseri(request $request)
    {
        // print_r($request->all());
        //    exit();
        $user = Auth::user();

        if ($user) {
            $about = About_Advisori::find($request->id);
            if ($about) {
                $about_adwiseri = About_Advisori::find($request->id);
                if (isset($request->advisori_details)) {
                    $about_adwiseri->heading = $request['heading'];
                    $about_adwiseri->content = $request['content'];
                    if ($request->hasFile('image')) {
                        $file = $request->file('image');
                        $extension = $file->getClientOriginalName();
                        $filename = time() . $extension;
                        $file->move('admin_assets/about_advisori/image/', $filename);
                        $about_adwiseri->image = $filename;
                    }
                    $about_adwiseri->save();
                    $activity = new Activities();
                    $activity->user_id = $user->id;
                    $activity->user_name = $user->name;
                    $activity->activity_name = "About Adwisari Updated";
                    $activity->activity_detail = "About Adwisari updated by " . $user->name . " at " . date('d M, Y H:i:s');
                    $activity->activity_icon = "user.png";
                    $activity->save();
                    return back()->with('success', 'About Adwisari Updated Successfully!');
                } elseif (isset($request->banner)) {
                    if ($request->hasFile('banner')) {
                        $file = $request->file('banner');
                        $extension = $file->getClientOriginalName();
                        $filename = time() . $extension;
                        $file->move('admin_assets/about_advisori/banner/', $filename);
                        $about_adwiseri->banner = $filename;
                    }
                    $about_adwiseri->save();
                    $activity = new Activities();
                    $activity->user_id = $user->id;
                    $activity->user_name = $user->name;
                    $activity->activity_name = "About Adwisari Updated";
                    $activity->activity_detail = "About Adwisari updated by " . $user->name . " at " . date('d M, Y H:i:s');
                    $activity->activity_icon = "user.png";
                    $activity->save();
                    return back()->with('success', 'Banner Updated Successfully!');
                }
            } else {
                return back();
            }
        } else {
            return redirect()->route('login');
        }
    }

    public function delete_user($id = null)
    {
        if (!empty($id)) { //edit the page.
            $user = Auth::user();
            if ($user) {
                $siteuser = User::find($id);
                $siteuser->delete();
                $activity = new Activities();
                $activity->user_id = $user->id;
                $activity->user_name = $user->name;
                $activity->activity_name = "User Deleted";
                $activity->activity_detail = "User " . $siteuser->name . " deleted by " . $user->name . " at " . date('d M, Y H:i:s');
                $activity->activity_icon = "user.png";
                $activity->save();
                return back()->with('deleted', 'user deleted successfully');
            } else {
                return redirect()->route('login');
            }
        } else { //view the page.
            return back();
        }
    }

    public function delete_clients($id = null)
    {
        if (!empty($id)) { //edit the page.
            $user = Auth::user();
            if ($user) {
                $client = Clients::find($id);
                $client->delete();
                $activity = new Activities();
                $activity->user_id = $user->id;
                $activity->user_name = $user->name;
                $activity->activity_name = "Client Deleted";
                $activity->activity_detail = "Client " . $client->name . " deleted by " . $user->name . " at " . date('d M, Y H:i:s');
                $activity->activity_icon = "user.png";
                $activity->save();
                return back()->with('deleted', 'client deleted successfully');
            } else {
                return redirect()->route('login');
            }
        } else { //view the page.
            return back();
        }
    }

    public function delete_application($id = null)
    {
        if (!empty($id)) { //edit the page.
            $user = Auth::user();
            if ($user) {
                $application = Applications::find($id);
                $application->delete();
                $activity = new Activities();
                $activity->user_id = $user->id;
                $activity->user_name = $user->name;
                $activity->activity_name = "Application Deleted";
                $activity->activity_detail = "Application " . $application->application_name . " deleted by " . $user->name . " at " . date('d M, Y H:i:s');
                $activity->activity_icon = "user.png";
                $activity->save();
                return back()->with('deleted', 'Application deleted successfully');
            } else {
                return redirect()->route('login');
            }
        } else { //view the page.
            return back();
        }
    }

    public function delete_document($id = null)
    {
        if (!empty($id)) { //edit the page.
            $user = Auth::user();
            if ($user) {
                $document = Client_Docs::find($id);
                $document->delete();
                $activity = new Activities();
                $activity->user_id = $user->id;
                $activity->user_name = $user->name;
                $activity->activity_name = "Document Deleted";
                $activity->activity_detail = "Document " . $document->doc_name . " deleted by " . $user->name . " at " . date('d M, Y H:i:s');
                $activity->activity_icon = "user.png";
                $activity->save();
                return back()->with('deleted', 'Document deleted successfully');
            } else {
                return redirect()->route('login');
            }
        } else { //view the page.
            return back();
        }
    }

    public function delete_app_assignment($id = null)
    {
        if (!empty($id)) { //edit the page.
            $user = Auth::user();
            if ($user) {
                $assignment = Application_assignments::find($id);
                $app_id = $assignment->application_id;
                $assignment->delete();
                $app = Applications::where('application_id', '=', $app_id)->first();
                $app->assign_to = "";
                $app->save();
                $activity = new Activities();
                $activity->user_id = $user->id;
                $activity->user_name = $user->name;
                $activity->activity_name = "App Assignment Deleted";
                $activity->activity_detail = "Application Assignment deleted by " . $user->name . " at " . date('d M, Y H:i:s');
                $activity->activity_icon = "user.png";
                $activity->save();
                return back()->with('deleted', 'Assignment deleted successfully');
            } else {
                return redirect()->route('login');
            }
        } else { //view the page.
            return back();
        }
    }

    public function delete_faq($id = null)
    {
        if (!empty($id)) { //edit the page.
            $user = Auth::user();
            if ($user) {
                $faq = Faq::find($id);
                $faq->delete();
                $activity = new Activities();
                $activity->user_id = $user->id;
                $activity->user_name = $user->name;
                $activity->activity_name = "FAQ Deleted";
                $activity->activity_detail = "FAQ " . $faq->question . " deleted by " . $user->name . " at " . date('d M, Y H:i:s');
                $activity->activity_icon = "user.png";
                $activity->save();
                return back()->with('deleted', 'Faq deleted successfully');
            } else {
                return redirect()->route('login');
            }
        } else { //view the page.
            return back();
        }
    }

    public function communication()
    {
        $user = Auth::user();
        if ($user) {
            $page = "communication";
            $messages = Messages::select('client_id')->orderBy('created_at', 'desc')->get();
            $a = array();
            foreach ($messages as $c) {
                array_push($a, $c->client_id);
            }
            $client_ids = array_unique($a);
            $clients = Clients::get();
            $messages = Internal_communications::orderBy('created_at', 'desc')->get();
            $subscribers = User::where('user_type', '=', 'Subscriber')->get();
            $users = User::where('user_type', '=', 'User')->get();
            $allusers = User::where('user_type', '!=', 'admin')->get();
            return view("admin.communication", compact('user', 'page', 'client_ids', 'clients', 'subscribers', 'users', 'allusers', 'messages'));
        } else {
            return redirect()->route('admin');
        }
    }
    public function manage_report_communications()
    {

        $startDate = $this->parseReportDate(request()->input('startDate'));
        $endDate = $this->parseReportDate(request()->input('endDate'), true);
        $user = Auth::user();
        $query = new Internal_communications();
        if($user->user_type == 'Subscriber'){
            $query = $query->where('subscriber_id',$user->id);

        }
        
        $messages = $query->whereBetween('created_at', [$startDate, $endDate])->orderBy('created_at', 'desc')->get();
        
        return DataTables::of($messages)
            ->addIndexColumn()
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('d-m-Y');
            })
            ->editColumn('sender_name', function ($row) {
                // Format the `start_date` for better readability

                return $row->sender_name . '(' . $row->send_by . ')';
            })
            ->addColumn('message', function ($row) {
                $text = htmlspecialchars($row->message);
                $words = explode(' ', $text);
                $truncated = implode(' ', array_slice($words, 0, 25)); // First 25 words

                return $row->message;
                // return '<div class="message-tooltip" data-full-text="' . htmlspecialchars($text) . '">
                //         <span class="hover-expand">' . $truncated . '...</span>
                //     </div>';
            })
            ->addColumn('receiver_name', function ($row) {
                $receivers = json_decode($row->receiver_name, true);
                $text = $receivers ? implode(', ', $receivers) : 'No receivers'; // Join with comma and space between items
                $words = explode(', ', trim($text)); // Split by comma and space
                $chunks = array_chunk($words, 3); // Split into groups of 3
                $formattedNames = array_map(fn($chunk) => implode(', ', $chunk), $chunks); // Rejoin each group with commas and spaces
                return implode('<br>', $formattedNames);
               
            })
            
            ->rawColumns(['message', 'receiver_name'])
            ->toJson();
    }

    public function admin_messaging()
    {
        $user = Auth::user();
        if ($user) {
            $page = "communication";
            $clients = Clients::get();
            $subscribers = User::where('user_type', '=', 'Subscriber')->get();
            $users = User::where('user_type', '=', 'User')->get();
            $allusers = User::where('user_type', '!=', 'admin')->get();
            return view("admin.messaging", compact('user', 'page', 'clients', 'subscribers', 'users', 'allusers'));
        } else {
            return redirect()->route('admin');
        }
    }

    public function admin_communicate(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $this->validate($request, [
                'sendto' => 'required',
                'message' => 'required|string',
            ]);
            function communication_id()
            {
                $ch = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
                $id = "";
                for ($i = 0; $i < 7; $i++) {
                    $id = $id . $ch[rand(0, strlen($ch) - 1)];
                }
                if (Internal_communications::where('communication_id', '=', $id)->first()) {
                    return communication_id();
                }
                return $id;
            }
            $communication_id = communication_id();
            $sendto = $request->sendto;
            $receiver_id = array();
            $receiver_name = array();

            if ($sendto) {
                if (count($sendto)) {
                    if (in_array('All Subscribers', $sendto)) {
                        $subscribers = User::where('user_type', '=', 'Subscriber')->get();
                        foreach ($subscribers as $subs) {
                            array_push($receiver_id, $subs->id);
                            array_push($receiver_name, $subs->name);
                        }
                        $admin_index = array_search('All Subscribers', $sendto);
                        array_splice($sendto, $admin_index, 1);
                    } elseif (in_array('All Users', $sendto)) {
                        $siteusers = User::where('user_type', '=', 'User')->get();
                        foreach ($siteusers as $suser) {
                            array_push($receiver_id, $suser->id);
                            array_push($receiver_name, $suser->name);
                        }
                        $admin_index = array_search('All Users', $sendto);
                        array_splice($sendto, $admin_index, 1);
                    } else {
                        if (count($sendto)) {
                            foreach ($sendto as $uid) {
                                $suser = User::find($uid);
                                array_push($receiver_id, $suser->id);
                                array_push($receiver_name, $suser->name);
                            }
                        }
                    }
                    $message = new Internal_communications();
                    $message->subscriber_id = $user->id;
                    $message->communication_id = $communication_id;
                    $message->user_id = $user->id;
                    $message->send_by = $user->id;
                    $message->send_to = json_encode($receiver_id, true);
                    $message->sender_name = $user->name;
                    $message->receiver_name = json_encode($receiver_name, true);
                    $message->message = $request['message'];
                    $message->save();
                    $activity = new Activities();
                    $activity->subscriber_id = $user->added_by ?  $user->added_by : $user->id ;
                    $activity->user_id = $user->id;
                    $activity->user_name =  $user->name;
                    $activity->activity_name = "New Message";
                    if (auth()->user()->user_type == "Subscriber") {
                        $activity->activity_detail = "New  message sent by" .  auth()->user()->name . " at " . $request->local_time;
                    } else {
                        $activity->activity_detail = "New  message sent by " .  auth()->user()->name . "(" . auth()->user()->name . ") at " . $request->local_time;
                    }
                    $activity->activity_icon = "invoice.jpg";
                    $activity->local_time = $request->local_time;
                    $activity->save();
                    return back()->with('sent', 'Message sent successfully!');
                } else {
                    return back()->with('noUser', 'No user selected');
                }
            } else {
                return back()->with('noUser', 'No user selected');
            }


            echo "Message Sent";
        } else {
            return redirect()->route('login');
        }
    }

    public function view_communication($id = null)
    {
        $user = Auth::user();
        if ($id) {
            $page = "communications";
            $message = Internal_communications::find($id);
            return view('admin.view_message', compact('message', 'user', 'page'));
        }
    }

    public function meetings()
    {
        $user = Auth::user();
        if ($user) {
            $page = "communication";
            // $subscribers = User::where('user_type', '=', 'Subscriber')->get();

            $subscribers = User::where('user_type', 'Subscriber')->get();
            return view("admin.meetings", compact('user', 'page', 'subscribers'));
        } else {
            return redirect()->route('admin');
        }
    }

    public function notes($id = null)
    {
        $user = Auth::user();
        if ($user) {
            $page = "communication";
            if ($id) {
                $subscriber = User::find($id);
                $clients = Clients::where('subscriber_id', '=', $subscriber->id)->get();
                $notes = Client_discussions::where('subscriber_id', '=', $subscriber->id)->get();
                return view("admin.notes", compact('user', 'page', 'subscriber', 'notes', 'clients'));
            } else {
                return back();
            }
        } else {
            return redirect()->route('admin');
        }
    }

    public function chat($id = null)
    {
        if (!empty($id)) { //edit the page.
            $user = Auth::user();
            if ($user) {
                $page = "communication";
                $client = Clients::find($id);
                $messages = Messages::where('client_id', '=', $client->id)->orderBy('created_at', 'desc')->get();
                return view('admin.chat', compact('user', 'page', 'client', 'messages'));
            } else {
                return redirect()->route('login');
            }
        } else { //view the page.
            return back();
        }
    }

    public function send_response(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $message = new Messages();
            $message->admin_id = $request['admin_id'];
            $message->client_id = $request['client_id'];
            $message->message = $request['message'];
            $message->save();
            echo "Message Sent";
        } else {
            return back();
        }
    }

    public function view_query($id)
    {
        $user = Auth::user();
        if ($user) {
            if ($id) {
                $query = Tickets::find($id);
                $page = "support";
                return view('admin.view_query', compact('user', 'page', 'query'));
            }
        } else {
            return redirect()->route('admin');
        }
    }

    public function update_query_status($id)
    {
        $user = Auth::user();
        if ($user) {
            if ($id) {
                $query = Tickets::find($id);
                if ($query->status == "Open") {
                    $query->status = "Closed";
                } else {
                    $query->status = "Open";
                }
                $query->save();
                return back()->with('status_changed', 'query status changed.');
            }
        } else {
            return redirect()->route('admin');
        }
    }

    public function query_response($id)
    {
        $user = Auth::user();
        if ($user) {
            if ($id) {
                $query = Tickets::find($id);
                $page = "support";
                return view('admin.query_response', compact('user', 'page', 'query'));
            }
        } else {
            return redirect()->route('admin');
        }
    }

    public function send_query_response(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            if ($request) {
                $query = Tickets::find($request->id);
                $query->response = $request['response'];
                $query->save();
                return redirect()->route('manage_support')->with('response', 'Response Sent Successfully.');
            }
        } else {
            return redirect()->route('admin');
        }
    }

    public function delete_query($id)
    {
        $user = Auth::user();
        if ($user) {
            if ($id) {
                $query = Tickets::find($id);
                $query->delete();
                return back()->with('query_deleted', 'Query Delete Successfully');
            }
        } else {
            return redirect()->route('admin');
        }
    }

    public function get_job_role(Request $request)
    {
        // print_r($request->all());
        $id = $request['id'];
        if (isset($request->name)) {
            $subscriber = User::find($id);
        } else {
            $client = Clients::find($id);
            $subscriber = User::find($client->subscriber_id);
        }
        if ($subscriber->category == "Law Firm") {
            $client_jobs = Client_jobs::where('category', '=', $subscriber->category)->get();
        } elseif ($subscriber->category == "Travel Agency") {
            $client_jobs = Client_jobs::where('category', '=', $subscriber->category)->get();
        } else {
            $client_jobs = Client_jobs::where('category', '=', $subscriber->category)->where('sub_category', '=', $subscriber->sub_category)->get();
        }
        if ($subscriber->sub_category == "Other") {
?>
            <option value="">Select Application</option>
            <option value="<?php echo $subscriber->other_subcategory; ?>"><?php echo $subscriber->other_subcategory; ?></option>
        <?php
        } else {
        ?>
            <option value="">Select Application</option>
            <?php
            foreach ($client_jobs as $job) {
            ?>
                <option value="<?php echo $job->job; ?>"><?php echo $job->job; ?></option>
        <?php
            }
        }
    }

    public function fetch_visa_country($id){
        // dd($id);
        // $id = $request['id'];
        // if (isset($request->name)) {
        //     $subscriber = User::find($id);
        // } else {
        //     $client = Clients::find($id);
        //     $subscriber = User::find($client->subscriber_id);
        // }
        $visa_country = Clients::where('id', '=', $id)->first();
        return $visa_country->visa_country;

    }

    public function get_client(Request $request)
    {
        // print_r($request->all());
        $id = $request['id'];
        $client = Clients::find($id);
        $application = Applications::where('client_id', $client->id)->first();
        ?>
        <option value="<?php echo $application->id; ?>"><?php echo $application->application_name . "(" . $application->id . ")"; ?></option>
    <?php
    }

    public function get_applications(Request $request)
    {
        // print_r($request->all());
        $id = $request['id'];
        $applications = Applications::where('client_id', '=', $id)->where('assign_to', '=', null)->get();
    ?>
        <option value="">Select Application</option>
        <?php
        foreach ($applications as $app) {
        ?>
            <option value="<?php echo $app->application_id; ?>"><?php echo "" . $app->application_name . " (" . $app->application_id . ")"; ?></option>
        <?php
        }
    }

    public function get_user(Request $request)
    {
        // print_r($request->all());
        $client = Clients::find($request->id);
        $users = User::where('added_by', '=', $client->subscriber_id)->orwhere('id', '=', $client->subscriber_id)->get();
        ?>
        <option value="">Select User/Advisor</option>
        <?php
        foreach ($users as $u) {
        ?>
            <option value="<?php echo $u->id; ?>"><?php echo $u->name . "(" . $u->id . ")"; ?></option>
<?php
        }
    }

    public function settings()
    {
        $user = Auth::user();
        if ($user) {
            $tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
            $page = "settings";
            $countries = Countries::get();
            $currencies = Currency::orderBy('currency_code')->get();
            $inv_setting = Invoice_settings::find($user->id);
            $subscribers = User::where('user_type', 'Subscriber')->where('membership', '!=', 'Free')->where('membership_type', 'Subscription')->orderBy('name')->get();
            $reportSetting = ReportSetting::where('user_id', $user->id)->first();
            $emailTemplates = app(EmailTemplateService::class)->getTemplatesForSettings($user);
            $emailTemplateAudience = strtolower($user->user_type) === 'admin' ? 'admin' : 'subscriber';
            $reportModules = [
                'clients' => 'Clients',
                'applications' => 'Applications',
                'invoices' => 'Invoices',
                'payments' => 'Payments',
                'referrals' => 'Referrals',
                'wallets' => 'Wallets',
                'subscribers' => 'Subscribers',
                'affiliates' => 'Affiliates',
            ];

            return view('admin.settings', compact('tzlist', 'user', 'page', 'countries', 'currencies', 'inv_setting', 'subscribers', 'reportSetting', 'reportModules', 'emailTemplates', 'emailTemplateAudience'));
        } else {
            return redirect()->route('admin');
        }
    }

    public function invoice_settings(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $validated = $request->validate([
                'tax' => 'nullable|numeric|min:0|max:100',
                'discount' => 'nullable|numeric|min:0|max:100',
                'payment_link' => ['nullable','string'],
            ]);

            $setting = Invoice_settings::where('user_id',$user->id)->first();
            if ($setting) {
                $message = 'Invoice Settings Updated successfully';
                // $setting->name = $request['name'];
                // $setting->phone = $request['phone'];
                // $setting->email = $request['email'];
                // $setting->country = $request['country'];
                // $setting->state = $request['state'];
                // $setting->city = $request['city'];
                // $setting->pincode = $request['pincode'];
                $setting->payment_link = $validated['payment_link'] ?? null;
                $setting->tax = $validated['tax'] ?? 0;
                $setting->discount = $validated['discount'] ?? 0;
                // $setting->description = $request['description'];
                $setting->save();
                // return redirect()->back()->with('setting_saved', "Invoice Settings Saved");

            } else {
                $data = $validated;
                $data['user_id'] = $user->id;
                $data['tax'] = $data['tax'] ?? 0;
                $data['discount'] = $data['discount'] ?? 0;
                Invoice_settings::create($data);
                $message = 'Invoice Settings Saved successfully';


            }
            return response()->json([
                    'success' => true,
                    'message' => $message,

                ]);
        } else {
            return redirect()->route('admin');
        }
    }

    public function update_currency(Request $request)
    {
        $validated = $request->validate([
            'currency' => 'required|string|max:20',
            'timezone' => 'required|timezone',
        ]);

        $user = Auth::user();
        if ($user) {
            $user->currency = $validated['currency'];
             $user->timezone = $validated['timezone'];
            $user->save();
            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'currency' => $user->currency,
                'timezone' => $user->timezone
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
    }

    public function update_timezone(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $user->timezone = $request['timezone'];
            $user->save();
            echo "timezone_updated";
        } else {
            return redirect()->route('admin');
        }
    }
    public function check_login()
    {
        $user = Auth::user();
        if ($user) {
            return $user;
        } else {
            return redirect()->route('login');
        }
    }
    public function set_timezone()
    {
        $user = Auth::user();
        if ($user) {
            // date_default_timezone_set($user->timezone);
        }
    }



    public function analytics()
    {
        $user = $this->check_login();
        if ($user->user_type != "admin" && (new DateTime($user->membership_expiry_date)) < (new DateTime("now"))) {
            return redirect()->route('user_membership')->with("price_plan_expiry", "Please renew or upgrade price plan.");
        }

        $this->set_timezone();

        $activity = new Activities();
        $activity->user_id = $user->id;
        $activity->user_name = $user->name;
        $activity->activity_name = "Performed Analytics";
        $activity->activity_detail = "Analytics Performed by " . $user->name . " at " . date('d M, Y H:i:s');
        $activity->activity_icon = "user.png";
        $activity->save();

        if (auth()->user()->user_type == 'admin') {

            $subscribers = User::where('user_type', 'Subscriber')->where('name', '!=', 'ADMIN (adwiseri.com)')->pluck('id', 'name');
        } else {
            $subscribers = User::where('id', auth()->user()->id)->where('user_type', 'Subscriber')->where('name', '!=', 'ADMIN (adwiseri.com)')->pluck('id', 'name');
        }
        $page = "analytics";
         $countries = Countries::get();
         $affiliates = User::where('user_type', 'Affiliate')->where('name', '!=', 'ADMIN (adwiseri.com)')->pluck('id', 'name');
        return view('admin.analytics', compact('page', 'user', 'subscribers','countries','affiliates'));
    }


    public function Affiliates()
    {

        $user = $this->check_login();
        if ($user->user_type != "admin" && $user->user_type != "admin" && (new DateTime($user->membership_expiry_date)) < (new DateTime("now"))) {
            return redirect()->route('user_membership')->with("price_plan_expiry", "Please renew or upgrade price plan.");
        }
        $affiliates =  User::where('user_type', 'Affiliate')
        // ->withSum('affiliateTotalCommission as total_commission', 'amount_added')
        // ->having('total_commission', '>', 0)
        ->where('wallet', '>', 0)
        ->get();
        $page = "affiliates";
        $countries = Countries::get();

        return view('admin.affiliates', compact('page', 'user', 'affiliates', 'countries'));
    }
    public function getCommision()
    {
        $affiliate =  request()->affiliateid;
        $affiliate = Affiliates::with('user')->find($affiliate);

        $user = $affiliate->user->referral;
        $ace = AffiliateCommissionEarnt::where('referral_code', $user)
        ->first();
        if(empty($ace)){
            $ace['total_earned'] = $affiliate->user->wallet;
             $ace['paid_amount'] = 0;

        }
        return response()->json(['data' => $ace]);
    }
    public function affiliateCommissionPaid(Request $request)
    {
        $affiliateID = $request->affiliateID;
        $commissionPay = $request->commissionpay;
        $commissionearnt = explode(" ", $request->commissionearnt);
        $commissionearnt = $commissionearnt[1];
        $affiliate = Affiliates::with('user')->find($affiliateID);
        $user = $affiliate->user->referral;


        // dd($affiliate->user->update(['wallet',($affiliate->user->wallet - $commissionPay)]));
        $ace = AffiliateCommissionEarnt::where('referral_code', $user)->first();

        if (isset($ace)) {

            if ( $commissionPay > $ace->pending_amount   ) {

                return redirect()->back()->withErrors(['msg' => 'Trying to pay more than commission earned']);
            }


            // $affiliate->user->update(['wallet',($affiliate->user->wallet - $commissionPay)]);
            $ace->paid_amount += $commissionPay;
            $ace->pending_amount -=  $commissionPay;
            $ace->last_paid_at = date("Y-m-d H:m:s");
            $ace->save();


            // return redirect()->back()->with(['msg' => 'Commission Paid']);
        } else {

            $ace = new AffiliateCommissionEarnt();
            $ace->total_earned = $affiliate->user->wallet;
            $ace->referral_code =  $affiliate->user->referral;
            $ace->paid_amount = $commissionPay;
            $ace->pending_amount = $affiliate->user->wallet - $commissionPay;
            $ace->last_paid_at = date("Y-m-d H:m:s");
            $ace->save();


        }
        // dd($affiliate->user);

            $save_referral = new Referrals();
            $save_referral->referral_code =  $affiliate->user->referral;
            $save_referral->userid =  $affiliate->user->id;
            $save_referral->user_name =  $affiliate->user->name;
            $save_referral->total_amount =  $affiliate->user->wallet;
            $save_referral->type = 'Payout';
            $save_referral->debit_amount = $commissionPay;
            $save_referral->previous_balance =  $affiliate->user->wallet;
            $save_referral->wallet_balance = ( $affiliate->user->wallet - $commissionPay);
            $save_referral->save();
            $affiliate->user->wallet -= $commissionPay;
            $affiliate->user->save(); // Save the updated wallet value
            return redirect()->back()->with(['msg' => 'Commission Paid']);

    }
    public function affiliateReportAdmin()
    {
        if (request()->ajax()) {

            $data = User::with('getAffiliate', 'getReferrals')->where('user_type', 'Affiliate')->orderBy('created_at','desc')->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('nameRef', function ($row) {
                    return !empty($user_ref) ? $user_ref->user_name . ' (' . $user_ref->id . ')' : '-';
                })
                ->addColumn('name', function ($row) {
                    return $row->name . " (" . $row->id . ")";
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at->format('d-m-Y H:i:s');
                })
                ->addColumn('type', function ($row) {
                    return $row->getAffiliate->type;
                })
                ->addColumn('country', function ($row) {
                    return $row->getAffiliate->country;
                })
                ->addColumn('city', function ($row) {
                    return $row->getAffiliate->city;
                })
                ->addColumn('commission', function ($row) {
                    return Used_referrals::where('referral_code', $row->referral)->sum('commission_earnt');
                })
                ->addColumn('totalCommissionEarnt', function ($row) {
                    $ace = AffiliateCommissionEarnt::where('referral_code', $row->referral)->first();
                    return !empty($ace) ? '$ ' . $ace->total_earned . ' / $ ' . $ace->paid_amount : '0';
                })
                ->addColumn('transactionDate', function ($row) {
                    $transaction = AffiliateCommissionEarnt::where('referral_code', $row->referral)->first();
                    return !empty($transaction) ? $transaction->last_paid_at : '0';
                })

                ->addColumn('commission_single', function ($row) {
                    $comm = Used_referrals::where('subscriber_id', $row->id)->first();
                    return !empty($comm) ? $comm->commission_earnt : '0';
                })
                ->addColumn('action', function ($row) {
                    return '<input type="checkbox" onclick="clickStatus(\'' . $row->getAffiliate->id . '\')" ' . ($row->getAffiliate->status == 1 ? 'checked' : '') . '>';
                })
                ->addColumn('plan', function ($row) {
                    $plan = User::where('referral_code', $row->referral)->first();
                    return !empty($plan) ? $plan->membership : '-';
                })

                ->addColumn('membership_duration', function ($row) {
                    $mm = User::where('referral_code', $row->referral)->first();
                    if (!empty($mm)) {

                        $endMembership =  Carbon::parse($mm->membership_expiry_date);
                        $startMembership =  Carbon::parse($mm->membership_start_date);
                        $daysDifference = $startMembership->diffForHumans($endMembership, [
                            'parts' => 1,  // Maximum of 3 parts
                            'syntax' => CarbonInterface::DIFF_RELATIVE_AUTO,
                            'join' => ', '
                        ], false);
                        return strtoupper(str_replace(['before', 'after'], "", $daysDifference));
                    } else {
                        return '-';
                    }
                })
                ->addColumn('transaction', function ($row) {


                    if ($row->debit_amount) {
                        return $row->debit_amount . "(Dr)";
                    }
                    return $row->total_amount . "(Cr)";
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }
    public function affiliateReferralReportAdmin()
    {
        if (request()->ajax()) {

            // $data = Referrals::with('user', 'getUser')->whereHas('user', function ($query) {
            //     $query->where('user_type', 'Affiliate');
            // });
            $data = User::whereNotNull('referral_code')
            ->whereHas('getReferralUser')
            ->whereHas('getReferralUser.getRefferedByUser')
            ->with('getReferralUser')->where('user_type','Subscriber')->orderBy('created_at', 'desc')->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('affName', function ($row) {
                    // return !empty($row->refs->getUser) ? $row->refs->getUser->name . ' (' . $row->refs->getUser->id . ')' : '-';
                    return $row->getReferralUser->getRefferedByUser ? $row->getReferralUser->getRefferedByUser->name.'('.$row->getReferralUser->getRefferedByUser->id.')':'';
                })
                ->addColumn('sub', function ($row) {
                    return $row->name . ' (' . $row->id . ')';
                })
                ->addColumn('referral_code', function ($row) {
                    return $row->referral_code ;
                })
                ->addColumn('plan', function ($row) {
                    return $row->membership ;
                })
                ->addColumn('membership_duration', function ($row) {
                    $years = Carbon::parse($row->membership_start_date)->diffInYears(Carbon::parse($row->membership_expiry_date));
                 if($years > 0){
                   return  $years.' year';
                 }
                 return '--';

                })
                ->addColumn('total_amount', function ($row) {
                    return !empty($row->getReferralUser->total_amount) ? $row->getReferralUser->total_amount : '' ;
                })

                ->addColumn('amount_added', function ($row) {
                    return !empty($row->getReferralUser->amount_added) ? $row->getReferralUser->amount_added : '' ;
                })
                ->editColumn('created_at', function ($row) {
                    return $row->getReferralUser->created_at->format('d-m-Y');
                })


                ->make(true);
        }
    }
    public function affiliateWalletReportAdmin()
    {
        if (request()->ajax()) {

            // $data  = User::whereHas('refs')->with(['refs' => function ($query) {
            //     $query->orderBy('created_at', 'desc'); // Order the refs by created_at
            // }]);
            $data = Referrals::whereHas('user')
            ->whereHas('getRefferedByUser')
            ->with(['user'])
            ->orderBy('created_at', 'desc')->get();


            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('affName', function ($row) {
                    return !empty($row->getRefferedByUser) ? $row->getRefferedByUser->name . ' (' . $row->getRefferedByUser->id . ')' : '-';
                })
                ->addColumn('transaction', function ($row) {


                        if (!empty($row->wallet_balance) && $row->wallet_balance > $row->previous_balance) {
                            // New balance is greater, so return "+" with the difference
                            return "+" . ($row->wallet_balance - $row->previous_balance);
                        } elseif (!empty($row->wallet_balance)  && $row->wallet_balance < $row->previous_balance) {
                            // New balance is less, so return "-" with the difference
                            return "-" . ($row->previous_balance - $row->wallet_balance);
                        } else {
                            // Balances are equal
                            return "0 (Balanced)";
                        }

                })
                ->addColumn('totalCommissionEarnt', function ($row) {
                    if ($row->refs) {
                        return $row->type;

                    }

                })

                ->addColumn('transactionDate', function ($row) {
                    // $transaction = AffiliateCommissionEarnt::where('referral_code', $row->referral)->first();
                    // return !empty($transaction) ? $transaction->last_paid_at : '-';
                    return $row->created_at->format('d-m-Y');
                })
                ->make(true);
        }
    }

    public function changeAffiliateStatus()
    {
        $affiliate_id = request()->id;
        $aff = Affiliates::find($affiliate_id);
        $status = $aff->status;
        if ($status == 1) {
            $aff->status = 0;
        } else {
            $aff->status = 1;
        }
        $aff->save();
        return response()->json(['msg' => 'success']);
    }
    public function affiliates_referrals()
    {
        $user = $this->check_login();
        if ($user->user_type != "admin" && $user->user_type != "admin" && (new DateTime($user->membership_expiry_date)) < (new DateTime("now"))) {
            return redirect()->route('user_membership')->with("price_plan_expiry", "Please renew or upgrade price plan.");
        }
        $page = "affiliates";
        return view('admin.affiliates_referrals', compact('page', 'user'));
    }
    public function affiliates_wallet()
    {
        $user = $this->check_login();
        if ($user->user_type != "admin" && $user->user_type != "admin" && (new DateTime($user->membership_expiry_date)) < (new DateTime("now"))) {
            return redirect()->route('user_membership')->with("price_plan_expiry", "Please renew or upgrade price plan.");
        }
        $page = "affiliates";
        return view('admin.affiliates_wallet', compact('user', 'page'));
    }
    // public function applyOffer(Request $request)
    // {
    //     $validated = $request->validate([
    //         'subscribers' => [
    //             'required',
    //             'array',
    //             function ($attribute, $value, $fail) {
    //                 if (!in_array('All', $value) && empty($value)) {
    //                     $fail('Please select at least one subscriber.');
    //                 }
    //             },
    //         ],
    //         'subscribers.*' => [
    //             function ($attribute, $value, $fail) {
    //                 if ($value !== 'All' && !\App\Models\User::where('id', $value)->exists()) {
    //                     $fail('Invalid subscriber selected.');
    //                 }
    //             },
    //         ],
    //         'discount_type' => 'required|string|in:cashback,one_off,double_term',
    //         'discount_value' => 'nullable|numeric|min:1|required_if:discount_type,cashback,one_off',
    //     ]);

    //     $subscribe = $validated['subscribers'];
    //     $type = $validated['discount_type'];
    //     $value = $validated['discount_value'];
    //     $subscriberData = [];
    //     if (in_array('all',  $subscribe)) {
    //         $subscribers = User::where('user_type', 'Subscriber')->get();
    //     } else {
    //         $subscribers = User::whereIn('id', $subscribe)->get();
    //     }
    //     foreach ($subscribers as $subscriber) {


    //         if ($type === 'cashback') {
    //             $member = Membership::where('plan_name', $subscriber->membership)->first();
    //             $previous_balance = $subscriber->wallet;
    //             $debit = $member->price_per_year * ($value / 100);
    //             $wallet_balance = ($subscriber->wallet + $debit);
    //             $subscriber->wallet += $member->price_per_year * ($value / 100);
    //         } elseif ($type === 'one_off') {

    //            $previous_balance = $subscriber->wallet;
    //            $wallet_balance = $subscriber->wallet + $value;
    //            $debit = $value;
    //            $subscriber->wallet += $value;

    //         } elseif ($type === 'double_term') {
    //             $subscriber->membership_expiry_date = Carbon::parse($subscriber->membership_expiry_date)->addYears(1);

    //             $debit =$subscriber->wallet;
    //             $wallet_balance = $subscriber->wallet;
    //             $previous_balance = $subscriber->wallet;
    //         }
    //        $offer =  Offers::create([
    //             'user_id' => $subscriber->id,
    //             'discount_type' => $type,
    //             'discount_value' => $value
    //         ]);

    //         $save_referral = new Referrals();
    //         $save_referral->referral_code =  $subscriber->referral;
    //         $save_referral->userid = $subscriber->id;
    //         $save_referral->user_name =  $subscriber->name;
    //         $save_referral->total_amount = $subscriber->wallet;
    //         $save_referral->type = $type;
    //         $save_referral->amount_added = $debit;
    //         $save_referral->offer_id =  $offer->id;
    //         $save_referral->previous_balance =   $previous_balance;
    //         $save_referral->wallet_balance =  $wallet_balance ;
    //         $save_referral->save();

    //         $subscriber->save();


    //         $subscriberData[] = [
    //             'name' => $subscriber->name,
    //             'email' => $subscriber->email,
    //             'type' => $type,
    //             'value' => $value
    //         ];
    //     }
    //     Mail::send([], [], function ($message) use ($subscriberData) {
    //         foreach ($subscriberData as $subscriber) {
    //             $message->bcc($subscriber['email'])
    //                 ->subject('Offer && Discount!')
    //                 ->setBody(
    //                     view('web.offer_appliedtemplate', $subscriber)->render(),
    //                     'text/html'
    //                 );
    //         }
    //     });

    //     return back()->with('offer_apply', 'Offer applied successfully!');
    // }

    public function applyOffer(Request $request)
    {
        $validated = $request->validate([
            'subscribers' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    if (!in_array('All', $value) && count(array_filter($value)) === 0) {
                        $fail('Please select at least one subscriber.');
                    }
                },
            ],
            'subscribers.*' => [
                function ($attribute, $value, $fail) {
                    if ($value !== 'All' && !\App\Models\User::where('id', $value)->exists()) {
                        $fail('Invalid subscriber selected.');
                    }
                },
            ],
            'discount_type' => 'required|string|in:cashback,one_off,double_term',
            'discount_value' => 'nullable|numeric|min:1|required_if:discount_type,cashback,one_off',
            'subscriber_type' => 'required|string|in:new,existing',
            'offer_start_date' => 'required_if:subscriber_type,new|nullable|date',
            'offer_end_date' => 'required_if:subscriber_type,new|nullable|date|after_or_equal:offer_start_date',
        ]);
    
        $subscribe = $validated['subscribers'];
        $type = $validated['discount_type'];
        $value = $validated['discount_value'] ?? null;
        $subscriberType = $validated['subscriber_type'];
        $offerStartDate = $validated['offer_start_date'] ?? null;
        $offerEndDate = $validated['offer_end_date'] ?? null;
        $subscriberData = [];
    
        // Handle "All" (case-insensitive)
        if (collect($subscribe)->contains(function ($item) {
            return strtolower($item) === 'all';
        })) {
            $subscriberQuery = User::where('user_type', 'Subscriber');
        } else {
            $subscriberQuery = User::whereIn('id', $subscribe)->where('user_type', 'Subscriber');
        }

        if ($subscriberType === 'new') {
            $subscriberQuery->whereBetween('created_at', [
                Carbon::parse($offerStartDate)->startOfDay(),
                Carbon::parse($offerEndDate)->endOfDay(),
            ]);
        }

        $subscribers = $subscriberQuery->get();

        if ($subscribers->isEmpty()) {
            return response()->json([
                'message' => 'No subscribers found for the selected criteria.'
            ], 422);
        }
    
        foreach ($subscribers as $subscriber) {
            $debit = 0;
            $wallet_balance = $subscriber->wallet;
            $previous_balance = $subscriber->wallet;
    
            if ($type === 'cashback') {
                $member = Membership::where('plan_name', $subscriber->membership)->first();
                if (!$member) {
                    continue;
                }

                $debit = $member->price_per_year * ($value / 100);
                $subscriber->wallet += $debit;
                $wallet_balance = $subscriber->wallet;
    
            } elseif ($type === 'one_off') {
                $debit = $value;
                $subscriber->wallet += $value;
                $wallet_balance = $subscriber->wallet;
    
            } elseif ($type === 'double_term') {
                $subscriber->membership_expiry_date = Carbon::parse($subscriber->membership_expiry_date)->addYears(1);
                // Wallet doesn't change here
            }
    
            $offer = Offers::create([
                'user_id' => $subscriber->id,
                'discount_type' => $type,
                'discount_value' => $value,
                'subscriber_type' => $subscriberType,
                'offer_start_date' => $offerStartDate,
                'offer_end_date' => $offerEndDate,
            ]);
    
            Referrals::create([
                'referral_code' => $subscriber->referral,
                'userid' => $subscriber->id,
                'user_name' => $subscriber->name,
                'total_amount' => $subscriber->wallet,
                'type' => $type,
                'amount_added' => $debit,
                'offer_id' => $offer->id,
                'previous_balance' => $previous_balance,
                'wallet_balance' => $wallet_balance,
            ]);
    
            $subscriber->save();
    
            $subscriberData[] = [
                'name' => $subscriber->name,
                'email' => $subscriber->email,
                'type' => $type,
                'value' => $value,
                'credit_amount' => round($debit, 2),
                'description' => $type === 'one_off'
                    ? 'One-off Credit / Offer / Dispute Resolution'
                    : ($type === 'cashback' ? 'Discount / Cashback' : 'Double Term'),
            ];
        }

        // Send separate email to each subscriber so each one gets their own details.
        foreach ($subscriberData as $subscriber) {
            Mail::send([], [], function ($message) use ($subscriber) {
                $message->to($subscriber['email'])
                    ->subject('Wallet Credit / Offer Applied')
                    ->setBody(view('web.offer_appliedtemplate', $subscriber)->render(), 'text/html');
            });
        }

        return response()->json([
            'message' => 'Offer applied successfully!',
            'processed_subscribers' => count($subscriberData),
        ]);
    }
    

    public function admin_feedback(){

        $user = $this->check_login();
        if ($user->user_type != "admin" && $user->user_type != "admin" && (new DateTime($user->membership_expiry_date)) < (new DateTime("now"))) {
            return redirect()->route('user_membership')->with("price_plan_expiry", "Please renew or upgrade price plan.");
        }
        $feedbacks = Feedbacks::orderBy('created_at','desc')->get();
        $page = "feedbacks";
        return view('admin.admin_feedback', compact('user', 'page','feedbacks'));
    }

}
//jkj
// adminurl:  https://dazzingshadow.com/kanjee/public/admin
// userid :    admin@kanjee.com
// password:   123456789

// user url:   https://dazzingshadow.com/kanjee/public/
// userid:     sanju@gmail.com
// password:   123456789
