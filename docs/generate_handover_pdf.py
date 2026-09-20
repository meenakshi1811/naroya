#!/usr/bin/env python3
"""Generate Noraya Project Handover Document (PDF)."""

from datetime import date
from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_JUSTIFY, TA_LEFT, TA_RIGHT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import cm, mm
from reportlab.platypus import (
    CondPageBreak,
    KeepTogether,
    ListFlowable,
    ListItem,
    PageBreak,
    Paragraph,
    SimpleDocTemplate,
    Spacer,
    Table,
    TableStyle,
    HRFlowable,
)

OUTPUT = Path(__file__).resolve().parent / "Noraya_Project_Handover_Document.pdf"
AUTHOR = "Meenakshi Nanta"
PROJECT = "Noraya"
DOC_DATE = date.today().strftime("%d %B %Y")

# Brand colours
NAVY = colors.HexColor("#0B3A5B")
TEAL = colors.HexColor("#1A6B7A")
LIGHT = colors.HexColor("#F4F7FA")
BORDER = colors.HexColor("#D0D7DE")
TEXT = colors.HexColor("#1F2933")
MUTED = colors.HexColor("#52606D")


def make_styles():
    base = getSampleStyleSheet()
    styles = {
        "cover_title": ParagraphStyle(
            "cover_title",
            parent=base["Title"],
            fontName="Helvetica-Bold",
            fontSize=28,
            textColor=NAVY,
            alignment=TA_CENTER,
            spaceAfter=8,
            leading=34,
        ),
        "cover_sub": ParagraphStyle(
            "cover_sub",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=14,
            textColor=TEAL,
            alignment=TA_CENTER,
            spaceAfter=6,
            leading=18,
        ),
        "cover_meta": ParagraphStyle(
            "cover_meta",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=11,
            textColor=MUTED,
            alignment=TA_CENTER,
            spaceAfter=4,
            leading=15,
        ),
        "h1": ParagraphStyle(
            "h1",
            parent=base["Heading1"],
            fontName="Helvetica-Bold",
            fontSize=16,
            textColor=NAVY,
            spaceBefore=16,
            spaceAfter=8,
            leading=20,
        ),
        "h2": ParagraphStyle(
            "h2",
            parent=base["Heading2"],
            fontName="Helvetica-Bold",
            fontSize=12.5,
            textColor=TEAL,
            spaceBefore=12,
            spaceAfter=6,
            leading=16,
        ),
        "h3": ParagraphStyle(
            "h3",
            parent=base["Heading3"],
            fontName="Helvetica-Bold",
            fontSize=11,
            textColor=TEXT,
            spaceBefore=8,
            spaceAfter=4,
            leading=14,
        ),
        "body": ParagraphStyle(
            "body",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=9.5,
            textColor=TEXT,
            alignment=TA_JUSTIFY,
            spaceAfter=6,
            leading=13,
        ),
        "bullet": ParagraphStyle(
            "bullet",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=9.5,
            textColor=TEXT,
            leftIndent=4,
            spaceAfter=2,
            leading=12.5,
        ),
        "toc": ParagraphStyle(
            "toc",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=10.5,
            textColor=TEXT,
            spaceAfter=5,
            leading=14,
        ),
        "footer": ParagraphStyle(
            "footer",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=8,
            textColor=MUTED,
            alignment=TA_CENTER,
        ),
        "table_cell": ParagraphStyle(
            "table_cell",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=8.5,
            textColor=TEXT,
            leading=11,
        ),
        "table_header": ParagraphStyle(
            "table_header",
            parent=base["Normal"],
            fontName="Helvetica-Bold",
            fontSize=8.5,
            textColor=colors.white,
            leading=11,
        ),
        "note": ParagraphStyle(
            "note",
            parent=base["Normal"],
            fontName="Helvetica-Oblique",
            fontSize=9,
            textColor=MUTED,
            spaceAfter=6,
            leading=12,
        ),
    }
    return styles


def bullets(items, styles):
    return ListFlowable(
        [ListItem(Paragraph(i, styles["bullet"]), leftIndent=12, bulletColor=TEAL) for i in items],
        bulletType="bullet",
        start="•",
        leftIndent=15,
        spaceBefore=2,
        spaceAfter=8,
    )


def simple_table(headers, rows, styles, col_widths=None):
    header_row = [Paragraph(h, styles["table_header"]) for h in headers]
    data = [header_row]
    for row in rows:
        data.append([Paragraph(str(c), styles["table_cell"]) for c in row])
    t = Table(data, colWidths=col_widths, repeatRows=1)
    t.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, 0), NAVY),
                ("TEXTCOLOR", (0, 0), (-1, 0), colors.white),
                ("BACKGROUND", (0, 1), (-1, -1), colors.white),
                ("ROWBACKGROUNDS", (0, 1), (-1, -1), [colors.white, LIGHT]),
                ("GRID", (0, 0), (-1, -1), 0.4, BORDER),
                ("VALIGN", (0, 0), (-1, -1), "TOP"),
                ("LEFTPADDING", (0, 0), (-1, -1), 5),
                ("RIGHTPADDING", (0, 0), (-1, -1), 5),
                ("TOPPADDING", (0, 0), (-1, -1), 4),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 4),
            ]
        )
    )
    return t


def add_header_footer(canvas, doc):
    canvas.saveState()
    page_w, page_h = A4
    # Top line
    canvas.setStrokeColor(NAVY)
    canvas.setLineWidth(1.2)
    canvas.line(1.8 * cm, page_h - 1.2 * cm, page_w - 1.8 * cm, page_h - 1.2 * cm)
    canvas.setFont("Helvetica", 8)
    canvas.setFillColor(MUTED)
    canvas.drawString(1.8 * cm, page_h - 1.05 * cm, f"{PROJECT} — Project Handover Document")
    canvas.drawRightString(page_w - 1.8 * cm, page_h - 1.05 * cm, "Confidential")
    # Footer
    canvas.setLineWidth(0.6)
    canvas.line(1.8 * cm, 1.4 * cm, page_w - 1.8 * cm, 1.4 * cm)
    canvas.drawString(1.8 * cm, 0.9 * cm, f"Prepared by {AUTHOR}")
    canvas.drawCentredString(page_w / 2, 0.9 * cm, f"Page {doc.page}")
    canvas.drawRightString(page_w - 1.8 * cm, 0.9 * cm, DOC_DATE)
    canvas.restoreState()


def build():
    styles = make_styles()
    doc = SimpleDocTemplate(
        str(OUTPUT),
        pagesize=A4,
        leftMargin=1.8 * cm,
        rightMargin=1.8 * cm,
        topMargin=1.8 * cm,
        bottomMargin=2.0 * cm,
        title=f"{PROJECT} Project Handover Document",
        author=AUTHOR,
    )
    story = []
    W = doc.width

    # ---------- COVER ----------
    story.append(Spacer(1, 3.2 * cm))
    story.append(HRFlowable(width="100%", thickness=2.5, color=NAVY, spaceAfter=12))
    story.append(Paragraph(PROJECT.upper(), styles["cover_title"]))
    story.append(Paragraph("Project Handover Document", styles["cover_sub"]))
    story.append(Paragraph("Telemedicine Backend Platform", styles["cover_meta"]))
    story.append(Spacer(1, 0.4 * cm))
    story.append(HRFlowable(width="40%", thickness=1, color=TEAL, spaceBefore=4, spaceAfter=18))
    story.append(Spacer(1, 1.2 * cm))

    cover_info = [
        ["Document Type", "Technical & Operational Handover"],
        ["Project Name", PROJECT],
        ["Prepared By", AUTHOR],
        ["Role", "Developer"],
        ["Document Date", DOC_DATE],
        ["Repository", "NorayaTech / Back-End-Code"],
        ["Classification", "Internal — Confidential"],
    ]
    cover_table = Table(
        [[Paragraph(f"<b>{r[0]}</b>", styles["body"]), Paragraph(r[1], styles["body"])] for r in cover_info],
        colWidths=[W * 0.35, W * 0.55],
    )
    cover_table.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (0, -1), LIGHT),
                ("BOX", (0, 0), (-1, -1), 0.8, NAVY),
                ("INNERGRID", (0, 0), (-1, -1), 0.4, BORDER),
                ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
                ("LEFTPADDING", (0, 0), (-1, -1), 10),
                ("RIGHTPADDING", (0, 0), (-1, -1), 10),
                ("TOPPADDING", (0, 0), (-1, -1), 7),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 7),
            ]
        )
    )
    story.append(cover_table)
    story.append(Spacer(1, 2.5 * cm))
    story.append(
        Paragraph(
            "This document provides a complete handover of the Noraya backend system, "
            "including architecture, modules, APIs, admin operations, integrations, "
            "scheduled jobs, and operational guidance for continuing development and support.",
            styles["note"],
        )
    )
    story.append(PageBreak())

    # ---------- TOC ----------
    story.append(Paragraph("Table of Contents", styles["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=TEAL, spaceAfter=10))
    toc_items = [
        "1. Introduction & Purpose",
        "2. Project Overview",
        "3. Technology Stack",
        "4. System Architecture & User Roles",
        "5. Core Business Modules",
        "6. Admin Panel Capabilities",
        "7. API Reference Summary",
        "8. Payment, Refund & Payout Flows",
        "9. Appointment Lifecycle",
        "10. Affiliate & Referral System",
        "11. Notifications & Emails",
        "12. Authentication & Security",
        "13. Database Models",
        "14. Scheduled Jobs",
        "15. Project Structure",
        "16. Environment & Configuration",
        "17. Deployment & Operations",
        "18. Handover Checklist",
    ]
    for item in toc_items:
        story.append(Paragraph(item, styles["toc"]))
    story.append(PageBreak())

    # ---------- 1 ----------
    story.append(Paragraph("1. Introduction & Purpose", styles["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=TEAL, spaceAfter=8))
    story.append(
        Paragraph(
            "This handover document has been prepared by <b>Meenakshi Nanta</b>, Developer of the "
            "<b>Noraya</b> project, to transfer complete technical and operational knowledge of the "
            "system to the receiving team. It covers what the platform does, how it is built, how "
            "day-to-day features work, and what is required to run, maintain, and extend the product.",
            styles["body"],
        )
    )
    story.append(
        Paragraph(
            "The intended audience includes backend developers, DevOps engineers, product owners, "
            "and administrators who will own ongoing delivery after handover.",
            styles["body"],
        )
    )

    # ---------- 2 ----------
    story.append(Paragraph("2. Project Overview", styles["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=TEAL, spaceAfter=8))
    story.append(
        Paragraph(
            "<b>Noraya</b> is a telemedicine platform that connects patients with doctors for "
            "online consultations. This repository is the <b>Laravel backend</b> that powers:",
            styles["body"],
        )
    )
    story.append(
        bullets(
            [
                "Doctor mobile / web application APIs",
                "Patient mobile application APIs",
                "Admin web panel (Blade + AdminLTE-style UI)",
                "Public legal & CMS pages (privacy, terms, refund policy, disclaimer, about, contact)",
                "Affiliate referral registration pages and QR-based referral flows",
                "Flutter / SPA fallback via <font face='Courier'>public/index.html</font>",
            ],
            styles,
        )
    )
    story.append(Paragraph("Key product capabilities", styles["h2"]))
    story.append(
        bullets(
            [
                "Doctor registration with credential verification and admin approval",
                "Patient discovery: search, favourites, ratings, public doctor profiles",
                "Appointment booking with symptoms, accept/reject, payment, and cancellation",
                "Push notifications via Firebase Cloud Messaging (FCM)",
                "Prescriptions and post-consultation patient reviews",
                "Razorpay payment verification, refunds, and monthly doctor payout ledger",
                "Affiliate referral links so patients can register via a referral code",
                "Master data management: countries, languages, specialities, settings",
            ],
            styles,
        )
    )
    story.append(
        Paragraph(
            "Production references observed in the codebase include domains such as "
            "<b>app.noraya.in</b> and <b>doctor.noraya.in</b>, and the patient Android package "
            "default <font face='Courier'>com.app.norayapatient</font>. Source control is hosted "
            "under <b>NorayaTech / Back-End-Code</b> (branch typically <font face='Courier'>dev</font>).",
            styles["body"],
        )
    )

    # ---------- 3 ----------
    story.append(Paragraph("3. Technology Stack", styles["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=TEAL, spaceAfter=8))
    story.append(
        simple_table(
            ["Layer", "Technology"],
            [
                ["Framework", "Laravel 10 (PHP ^8.1)"],
                ["Database", "MySQL (Eloquent ORM)"],
                ["Admin UI", "Blade templates (AdminLTE-style)"],
                ["Auth — Doctors", "Laravel Passport (OAuth2 password grant)"],
                ["Auth — Patients", "Custom encrypted Bearer token middleware"],
                ["Auth — Admin", "Session guard against admins table"],
                ["Payments", "Razorpay (verify + refund APIs)"],
                ["Push notifications", "Firebase Cloud Messaging (HTTP v1 + Google service accounts)"],
                ["Email", "SMTP / Laravel Mailables"],
                ["Error monitoring", "Sentry (sentry/sentry-laravel)"],
                ["HTTP client", "Guzzle / Laravel HTTP"],
                ["Timezone", "Asia/Kolkata"],
            ],
            styles,
            col_widths=[W * 0.32, W * 0.68],
        )
    )

    # ---------- 4 ----------
    story.append(Paragraph("4. System Architecture & User Roles", styles["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=TEAL, spaceAfter=8))
    story.append(
        Paragraph(
            "The backend serves three primary roles. Clients communicate over HTTPS REST APIs; "
            "the admin panel uses server-rendered Blade views. Patients may also register through "
            "an affiliate referral link (affiliate is a registration/attribution path, not a login role).",
            styles["body"],
        )
    )
    story.append(
        simple_table(
            ["Role", "Storage", "Authentication", "Primary surface"],
            [
                ["Admin", "admins", "Session (web guard)", "/admin/* Blade panel"],
                ["Doctor", "users", "Passport OAuth2 (auth:api)", "Doctor app APIs"],
                ["Patient", "patients", "AuthenticateToken middleware", "Patient app APIs"],
            ],
            styles,
            col_widths=[W * 0.16, W * 0.18, W * 0.32, W * 0.34],
        )
    )
    story.append(Spacer(1, 0.3 * cm))
    story.append(
        Paragraph(
            "Doctors must have <font face='Courier'>chrApproval = Y</font> before most doctor "
            "features are available.",
            styles["body"],
        )
    )

    # ---------- 5 ----------
    story.append(Paragraph("5. Core Business Modules", styles["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=TEAL, spaceAfter=8))

    story.append(Paragraph("5.1 Doctor lifecycle", styles["h2"]))
    story.append(
        bullets(
            [
                "Register via <font face='Courier'>POST /api/register</font> (credentials, fees, speciality, languages, regulatory fields).",
                "Status remains pending until admin approval; registration email is sent.",
                "Admin approves → approval email (<font face='Courier'>SendApproval</font>).",
                "Doctor logs in via Passport password grant to <font face='Courier'>/oauth/token</font>.",
                "Manages availability, profile, bank details, appointment requests, and prescriptions.",
            ],
            styles,
        )
    )

    story.append(Paragraph("5.2 Patient lifecycle", styles["h2"]))
    story.append(
        bullets(
            [
                "Register via API or affiliate referral page (<font face='Courier'>PatientRegistrationService</font>).",
                "Login with email or phone; encrypted token stored in <font face='Courier'>patients.remember_token</font>.",
                "Browse / search doctors, favourite, fetch slots, book, pay, and submit feedback.",
            ],
            styles,
        )
    )

    story.append(Paragraph("5.3 Supporting modules", styles["h2"]))
    story.append(
        bullets(
            [
                "<b>Master data:</b> countries, states, languages, specialities (<font face='Courier'>dr_category</font>), general settings.",
                "<b>Favourites & ratings:</b> patient favourites and review ratings synced to doctor aggregates.",
                "<b>Doctor activity log:</b> admin-visible activity trail for doctors.",
                "<b>Book count:</b> booking counters reset by scheduled command based on settings.",
            ],
            styles,
        )
    )

    # ---------- 6 ----------
    story.append(Paragraph("6. Admin Panel Capabilities", styles["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=TEAL, spaceAfter=8))
    story.append(
        Paragraph(
            "Access: <font face='Courier'>/admin/login</font> → authenticated session. "
            "Sidebar navigation exposes the following modules:",
            styles["body"],
        )
    )
    story.append(
        simple_table(
            ["Module", "Path", "Capabilities"],
            [
                ["Doctor", "/admin/doctor", "List approved doctors, bank details, payments summary, delete, activity log, record payout"],
                ["Pending Approval", "/admin/pending-doctor", "Approve pending doctors and trigger approval email"],
                ["Patient", "/admin/patient", "List and delete patients"],
                ["Country", "/admin/country", "CRUD + publish flag"],
                ["Language", "/admin/language", "CRUD + publish"],
                ["Speciality", "/admin/speciality", "CRUD on doctor categories"],
                ["Appointment", "/admin/appointment", "Paid non-canceled appointments; filter by date, doctor, speciality, country, state, search"],
                ["Payment Logs", "/admin/payment-log", "Razorpay payments listing"],
                ["Payment Ledger", "/admin/payment-ledger", "Monthly gross / commission / refunds / payout; mark month paid; per-doctor view"],
                ["Affiliate", "/admin/affiliate", "CRUD affiliates, generate codes, default commission %, monthly/all-time stats, QR card"],
                ["Settings", "/admin/settings", "Slot duration, admin commission %, affiliate commission %, reset book date"],
                ["Profile", "/admin/profile", "Update admin profile"],
            ],
            styles,
            col_widths=[W * 0.2, W * 0.24, W * 0.56],
        )
    )
    story.append(Spacer(1, 0.25 * cm))
    story.append(
        Paragraph(
            "Admin can also initiate refunds via <font face='Courier'>POST /admin/refund</font> "
            "(same Razorpay refund handler used by the API).",
            styles["body"],
        )
    )

    # ---------- 7 ----------
    story.append(Paragraph("7. API Reference Summary", styles["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=TEAL, spaceAfter=8))
    story.append(
        Paragraph(
            "All API routes are prefixed with <font face='Courier'>/api</font>. "
            "Postman collections exist under the <font face='Courier'>postman/</font> directory.",
            styles["body"],
        )
    )

    story.append(Paragraph("7.1 Public endpoints", styles["h2"]))
    story.append(
        simple_table(
            ["Method", "Endpoint", "Purpose"],
            [
                ["POST", "/login", "Doctor login → Passport tokens"],
                ["POST", "/register", "Doctor registration"],
                ["GET", "/countries, /languages, /speciality, /state-list", "Master data"],
                ["POST", "/set-localization-language", "Set localization language"],
                ["POST", "/forget-password", "Password reset email"],
                ["GET", "/public/doctor/{id}", "Public doctor profile"],
                ["POST", "/refund", "Refund by payment id (throttled)"],
                ["POST", "/patient-login", "Patient login"],
                ["POST", "/patients-register", "Patient registration (optional affiliate_code)"],
            ],
            styles,
            col_widths=[W * 0.12, W * 0.42, W * 0.46],
        )
    )

    story.append(Paragraph("7.2 Doctor APIs (auth:api / Passport)", styles["h2"]))
    story.append(
        Paragraph(
            "Home dashboard, appointment requests (all / today / next), profile update, "
            "availability, accept/reject patient request, patient profile & history, "
            "schedule, prescription, bank details upsert/fetch, logout.",
            styles["body"],
        )
    )

    story.append(Paragraph("7.3 Patient APIs (AuthenticateToken)", styles["h2"]))
    story.append(
        Paragraph(
            "Home, view-all doctors, favourites, search, time slots, send booking request "
            "(throttle: booking), profile get/update, doctor profile, feedback, cancel appointment, "
            "clear conflict appointment, process payment (throttle: payment), my appointments, logout.",
            styles["body"],
        )
    )


    story.append(Paragraph("7.4 Web (non-API) routes of note", styles["h2"]))
    story.append(
        bullets(
            [
                "Admin authentication and all /admin/* management pages",
                "Affiliate referral registration: <font face='Courier'>GET/POST /refer/{code}</font>",
                "Legal pages: privacy, terms (patient/doctor), refund policy, disclaimer, about, contact",
                "Password reset: <font face='Courier'>password/reset/{token}</font>",
                "SPA fallback: unmatched non-asset routes serve <font face='Courier'>public/index.html</font>",
            ],
            styles,
        )
    )

    # ---------- 8 ----------
    story.append(Paragraph("8. Payment, Refund & Payout Flows", styles["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=TEAL, spaceAfter=8))

    story.append(Paragraph("8.1 Patient payment (Razorpay)", styles["h2"]))
    story.append(
        bullets(
            [
                "Patient books an appointment (unpaid by default: <font face='Courier'>charIsPaid = N</font>).",
                "Client completes Razorpay checkout and sends payment id, doctor, appointment id, and amount.",
                "<font face='Courier'>ProcessPayment</font> verifies the payment via Razorpay API (status captured).",
                "Writes a row in <font face='Courier'>payments</font> and updates appointment paid flag.",
            ],
            styles,
        )
    )

    story.append(Paragraph("8.2 Refunds", styles["h2"]))
    story.append(
        bullets(
            [
                "Loads payment record, verifies Razorpay status, refunds remaining amount (paise-aware).",
                "Marks payment status as refunded.",
                "Available from API (<font face='Courier'>throttle:payment</font>) and admin panel.",
            ],
            styles,
        )
    )

    story.append(Paragraph("8.3 Monthly doctor payout (admin ledger)", styles["h2"]))
    story.append(
        bullets(
            [
                "Ledger aggregates successful payments per doctor for a selected month.",
                "Commission = gross × <font face='Courier'>general_settings.percentage</font>.",
                "Final payout = gross − commission − refunds.",
                "Doctor bank details shown from <font face='Courier'>doctor_bank_details</font>.",
                "Admin marks month as paid → updates payment monthly_payout flags and related logs.",
                "Optional artisan command <font face='Courier'>payout:reset-monthly</font> resets doctor monthly flags (not in default schedule).",
            ],
            styles,
        )
    )

    # ---------- 9 ----------
    story.append(Paragraph("9. Appointment Lifecycle", styles["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=TEAL, spaceAfter=8))
    story.append(
        bullets(
            [
                "Patient selects doctor and date → fetches time slots (default window ~09:00–21:00; duration from settings).",
                "Patient sends request with symptoms → appointment created (unpaid) → FCM to doctor.",
                "Doctor accepts or rejects → FCM to patient.",
                "Patient pays via Razorpay → appointment marked paid.",
                "Conflict handling: overlapping paid slots block new bookings; unpaid own requests show as requested.",
                "Meeting reminder push ~15 minutes before the appointment.",
                "Doctor adds prescription after consultation.",
                "Patient may cancel (reason + FCM).",
                "Review reminder push ~15 minutes after appointment end; patient submits feedback/rating.",
            ],
            styles,
        )
    )

    # ---------- 10 ----------
    story.append(Paragraph("10. Affiliate & Referral System", styles["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=TEAL, spaceAfter=8))
    story.append(
        Paragraph(
            "Affiliate is not a system login role. It is a referral mechanism that lets patients "
            "register through a unique code; admin manages affiliate records and commission tracking.",
            styles["body"],
        )
    )
    story.append(
        bullets(
            [
                "Admin creates an affiliate record (custom person or linked doctor) with a unique referral code.",
                "Optional per-affiliate commission rate; otherwise default from settings (fallback ~3%).",
                "Patients register via <font face='Courier'>/refer/{code}</font> or by passing <font face='Courier'>affiliate_code</font> on API register.",
                "Referred patients store <font face='Courier'>affiliate_id</font> for attribution.",
                "Admin stats: referred users, paid non-canceled bookings, booking value, commission owed (month/year or all-time).",
                "Admin UI supports QR card export for sharing referral links.",
            ],
            styles,
        )
    )

    # ---------- 11 ----------
    story.append(Paragraph("11. Notifications & Emails", styles["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=TEAL, spaceAfter=8))
    story.append(Paragraph("11.1 Push notifications (FCM)", styles["h2"]))
    story.append(
        Paragraph(
            "Handled primarily via <font face='Courier'>NotificationController</font> using Firebase "
            "service account JSON files under <font face='Courier'>storage/app/firebase/</font> "
            "(doctor-app and patient-app). Examples: new appointment request, accept/reject, "
            "meeting reminder, review reminder.",
            styles["body"],
        )
    )
    story.append(Paragraph("11.2 Email notifications", styles["h2"]))
    story.append(
        simple_table(
            ["Mailable", "Trigger"],
            [
                ["DoctorRegistrationReceivedMail", "Doctor registers"],
                ["SendApproval", "Admin approves doctor"],
                ["ForgotPassword", "Forget-password API (reset link)"],
                ["UserOnboardingMail", "Bank / onboarding reminder"],
            ],
            styles,
            col_widths=[W * 0.4, W * 0.6],
        )
    )
    story.append(Spacer(1, 0.2 * cm))
    story.append(
        Paragraph("Email Blade templates live under <font face='Courier'>resources/views/emails/</font>.", styles["note"])
    )

    # ---------- 12 ----------
    story.append(Paragraph("12. Authentication & Security", styles["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=TEAL, spaceAfter=8))
    story.append(
        simple_table(
            ["Actor", "Mechanism", "Notes"],
            [
                ["Doctor", "Passport password grant", "PASSWORD_CLIENT_ID / SECRET; auth:api middleware"],
                ["Patient", "Custom encrypted Bearer", "Payload contains id; must match patients.remember_token"],
                ["Admin", "Session (web → admins)", "Standard form login / logout"],
            ],
            styles,
            col_widths=[W * 0.18, W * 0.32, W * 0.5],
        )
    )
    story.append(Spacer(1, 0.3 * cm))
    story.append(Paragraph("Rate limiting", styles["h2"]))
    story.append(
        bullets(
            [
                "<font face='Courier'>api</font>: 60 requests / minute (API group)",
                "<font face='Courier'>booking</font>: 10 / minute on patient send-request",
                "<font face='Courier'>payment</font>: 5 / minute on process-payment and refund",
            ],
            styles,
        )
    )
    story.append(
        Paragraph(
            "CORS is configured for doctor/patient web origins; CSP allows iframe embedding from "
            "<font face='Courier'>https://app.noraya.in</font> via <font face='Courier'>AllowIframeEmbedding</font>. "
            "Sentry is integrated for exception monitoring.",
            styles["body"],
        )
    )

    # ---------- 13 ----------
    story.append(Paragraph("13. Database Models", styles["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=TEAL, spaceAfter=8))
    story.append(
        simple_table(
            ["Model", "Table", "Purpose"],
            [
                ["User", "users", "Doctors (fees, approval, FCM, languages, payout flags, etc.)"],
                ["Patients", "patients", "Patients (phone, affiliate_id, token)"],
                ["Admin", "admins", "Admin users"],
                ["Appointment", "appointment", "Bookings, status flags, prescription, amount"],
                ["Payment", "payments", "Razorpay payment records + monthly_payout"],
                ["PaymentLog", "payment_logs", "Payment ledger logs"],
                ["RefundLog", "refund_logs", "Refund audit"],
                ["DoctorCredit", "doctor_credit", "Accrued / paid doctor amounts"],
                ["DoctorBankDetail", "doctor_bank_details", "Bank details for payouts"],
                ["Affiliate", "affiliates", "Referral codes used when patients register"],
                ["Rating / Favourite", "ratings / favourite", "Reviews and favourites"],
                ["BookCount", "book_count", "Booking counters (cron reset)"],
                ["GeneralSetting", "general_settings", "Key/value platform settings"],
                ["Country / State / Language / DrCategory", "country_master / states / language_master / dr_category", "Master data"],
                ["OrgExperience", "org_experiance", "Doctor work history"],
                ["DoctorActivity", "doctor_activities", "Admin activity trail"],
            ],
            styles,
            col_widths=[W * 0.28, W * 0.28, W * 0.44],
        )
    )
    story.append(Spacer(1, 0.25 * cm))
    story.append(
        Paragraph(
            "Note: Some core tables (for example appointment / patients) evolved earlier than the "
            "tracked migration set. For new environments, prefer a production DB dump plus "
            "running migrations, rather than migrations alone.",
            styles["note"],
        )
    )

    # ---------- 14 ----------
    story.append(Paragraph("14. Scheduled Jobs", styles["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=TEAL, spaceAfter=8))
    story.append(
        simple_table(
            ["Command", "Schedule", "Purpose"],
            [
                ["meeting:reminder", "Every minute", "FCM 15 minutes before paid, non-canceled appointment"],
                ["appointment:review-reminder", "Every minute", "FCM ~15 minutes after appointment end asking for review"],
                ["reset:bookcount", "Daily", "If reset_book_date ≤ now, truncate book_count and clear setting"],
            ],
            styles,
            col_widths=[W * 0.32, W * 0.2, W * 0.48],
        )
    )
    story.append(Spacer(1, 0.25 * cm))
    story.append(
        Paragraph(
            "Optional (not in default schedule): <font face='Courier'>payout:reset-monthly</font> "
            "resets doctors' monthly_payout flags. Host should run "
            "<font face='Courier'>php artisan schedule:run</font> every minute.",
            styles["body"],
        )
    )

    # ---------- 15 ----------
    story.append(Paragraph("15. Project Structure", styles["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=TEAL, spaceAfter=8))
    structure = """
<font face="Courier" size="8">
noraya/<br/>
&nbsp;&nbsp;app/<br/>
&nbsp;&nbsp;&nbsp;&nbsp;Console/Commands/&nbsp;&nbsp;&nbsp;# Cron / artisan commands<br/>
&nbsp;&nbsp;&nbsp;&nbsp;Http/Controllers/&nbsp;&nbsp;&nbsp;# API + web controllers<br/>
&nbsp;&nbsp;&nbsp;&nbsp;Http/Controllers/Admin/<br/>
&nbsp;&nbsp;&nbsp;&nbsp;Http/Middleware/&nbsp;&nbsp;&nbsp;&nbsp;# AuthenticateToken, CSP, etc.<br/>
&nbsp;&nbsp;&nbsp;&nbsp;Mail/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;# Mailables<br/>
&nbsp;&nbsp;&nbsp;&nbsp;Models/<br/>
&nbsp;&nbsp;&nbsp;&nbsp;Services/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;# PatientRegistration and related services<br/>
&nbsp;&nbsp;config/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;# auth, passport, services, sentry<br/>
&nbsp;&nbsp;database/migrations/<br/>
&nbsp;&nbsp;public/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;# Web root, profiles, SPA index.html<br/>
&nbsp;&nbsp;resources/views/admin/&nbsp;&nbsp;# Admin UI<br/>
&nbsp;&nbsp;resources/views/emails/<br/>
&nbsp;&nbsp;resources/views/referral/<br/>
&nbsp;&nbsp;routes/api.php, web.php<br/>
&nbsp;&nbsp;storage/app/firebase/&nbsp;&nbsp;# Service account JSONs (required)<br/>
&nbsp;&nbsp;postman/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;# API collections<br/>
&nbsp;&nbsp;staging/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;# Staging-related folder (confirm on server)
</font>
"""
    story.append(Paragraph(structure, styles["body"]))
    story.append(
        Paragraph(
            "Profile images are stored under <font face='Courier'>public/api/docterprofile/</font> "
            "and <font face='Courier'>public/api/patientprofile/</font>.",
            styles["body"],
        )
    )

    # ---------- 16 ----------
    story.append(Paragraph("16. Environment & Configuration", styles["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=TEAL, spaceAfter=8))
    story.append(
        Paragraph(
            "Configuration is driven by <font face='Courier'>.env</font>. Key variable groups "
            "(names only — never commit secrets):",
            styles["body"],
        )
    )
    story.append(
        bullets(
            [
                "<b>App:</b> APP_NAME, APP_ENV, APP_KEY, APP_DEBUG, APP_URL",
                "<b>Database:</b> DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD",
                "<b>Cache / session:</b> CACHE_DRIVER, SESSION_DRIVER, REDIS_*",
                "<b>Passport:</b> PASSWORD_CLIENT_ID, PASSWORD_CLIENT_SECRET",
                "<b>Mail:</b> MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_*",
                "<b>Razorpay:</b> RAZORPAY_KEY, RAZORPAY_SECRET",
                "<b>Firebase / FCM:</b> FIREBASE_PROJECT_ID, FCM_SERVER_KEY + service account JSON files",
                "<b>AWS / Pusher / Sentry:</b> AWS_*, PUSHER_*, SENTRY_LARAVEL_DSN",
            ],
            styles,
        )
    )

    # ---------- 17 ----------
    story.append(Paragraph("17. Deployment & Operations", styles["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=TEAL, spaceAfter=8))
    story.append(
        bullets(
            [
                "Document root must point to <font face='Courier'>public/</font> (Apache/Nginx or XAMPP local).",
                "<font face='Courier'>APP_URL</font> must be correct — Passport token endpoint depends on it.",
                "Run <font face='Courier'>php artisan schedule:run</font> every minute (cron or Windows Task Scheduler).",
                "Ensure Firebase JSON credentials exist under <font face='Courier'>storage/app/firebase/</font>.",
                "Ensure Razorpay keys are set for payment verification and refunds.",
                "Composer install, migrate (or restore DB dump), storage link, optimize config/cache as needed.",
                "Git remote: GitHub <b>NorayaTech/Back-End-Code</b>; active development branch commonly <font face='Courier'>dev</font>.",
            ],
            styles,
        )
    )
    story.append(Paragraph("Typical local setup (XAMPP)", styles["h2"]))
    story.append(
        bullets(
            [
                "Place project under htdocs; serve via Apache with PHP 8.1+.",
                "Create MySQL database and configure .env.",
                "Run <font face='Courier'>composer install</font> and application key generation if needed.",
                "Import schema / run migrations; configure Passport clients for doctor login.",
            ],
            styles,
        )
    )

    # ---------- 18 ----------
    story.append(Paragraph("18. Handover Checklist", styles["h1"]))
    story.append(HRFlowable(width="100%", thickness=1, color=TEAL, spaceAfter=8))
    story.append(
        Paragraph(
            "The receiving team should confirm possession of the following before assuming ownership:",
            styles["body"],
        )
    )
    story.append(
        simple_table(
            ["#", "Item", "Status"],
            [
                ["1", "Source code access (GitHub NorayaTech/Back-End-Code)", "☐"],
                ["2", "Production & staging server credentials / SSH / hosting panel", "☐"],
                ["3", "Database access + recent backup / dump", "☐"],
                ["4", ".env values for all environments (secure channel)", "☐"],
                ["5", "Razorpay dashboard access (keys, refunds)", "☐"],
                ["6", "Firebase projects + service account JSON files (doctor & patient)", "☐"],
                ["7", "Sentry project access", "☐"],
                ["8", "SMTP / mail provider credentials", "☐"],
                ["9", "Admin panel login credentials", "☐"],
                ["10", "Passport OAuth client id/secret", "☐"],
                ["11", "Cron / scheduler confirmation on servers", "☐"],
                ["12", "This handover document reviewed with stakeholders", "☐"],
            ],
            styles,
            col_widths=[W * 0.08, W * 0.72, W * 0.2],
        )
    )

    story.append(Spacer(1, 1.0 * cm))
    story.append(HRFlowable(width="100%", thickness=1.5, color=NAVY, spaceAfter=10))
    story.append(Paragraph("Document Control", styles["h2"]))
    story.append(
        Paragraph(
            f"<b>Prepared by:</b> {AUTHOR} &nbsp;&nbsp;|&nbsp;&nbsp; <b>Role:</b> Developer "
            f"&nbsp;&nbsp;|&nbsp;&nbsp; <b>Project:</b> {PROJECT} &nbsp;&nbsp;|&nbsp;&nbsp; "
            f"<b>Date:</b> {DOC_DATE}",
            styles["body"],
        )
    )
    story.append(
        Paragraph(
            "This document reflects the state of the Noraya backend codebase at the time of writing. "
            "Subsequent releases should update this handover or append release notes as part of "
            "standard change management.",
            styles["note"],
        )
    )
    story.append(Spacer(1, 0.8 * cm))
    story.append(
        Paragraph(
            "— End of Handover Document —",
            ParagraphStyle(
                "end",
                parent=styles["cover_meta"],
                alignment=TA_CENTER,
                textColor=NAVY,
                fontName="Helvetica-Bold",
            ),
        )
    )

    doc.build(story, onFirstPage=add_header_footer, onLaterPages=add_header_footer)
    print(f"Wrote: {OUTPUT}")


if __name__ == "__main__":
    build()
