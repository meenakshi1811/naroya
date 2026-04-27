<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark"> <!--begin::Sidebar Brand-->
            <div class="sidebar-brand"> <!--begin::Brand Link--> <a href="{{ url('/admin') }}" class="brand-link"> <!--begin::Brand Image--> <img src="{{ url('assets/img/logo.png') }}" alt="Naroya" class="brand-image opacity-75 shadow"> <!--end::Brand Image--> <!--begin::Brand Text--> <span class="brand-text fw-light">Naroya</span> <!--end::Brand Text--> </a> <!--end::Brand Link--> </div> <!--end::Sidebar Brand--> <!--begin::Sidebar Wrapper-->
            <div class="sidebar-wrapper">
                <nav class="mt-2"> <!--begin::Sidebar Menu-->
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                        <!-- <li class="nav-item"> <a href="{{ url('/admin') }}" class="nav-link active"> <i class="nav-icon bi bi-speedometer"></i>
                                <p>
                                    Dashboard                                   
                                </p>
                            </a>
                           
                        </li>  -->
                      
                        <li class="nav-item"> <a href="{{ url('/admin/doctor') }}" class="nav-link {{  (Request::segment('2') == 'doctor') ? 'active': ''}}"> <i class="nav-icon bi bi-circle"></i>
                                <p>Doctor</p>
                            </a> </li>
                         <li class="nav-item"> <a href="{{ url('/admin/pending-doctor') }}" class="nav-link {{  (Request::segment('2') == 'pending-doctor') ? 'active': ''}}"> <i class="nav-icon bi bi-circle"></i>
                            <p>Pending Approval Doctor</p>
                        </a> </li>
                        <li class="nav-item"> <a href="{{ url('/admin/patient') }}" class="nav-link {{ (Request::segment('2') == 'patient') ? 'active': ''}}"> <i class="nav-icon bi bi-circle"></i>
                                <p>Patient</p>
                            </a> </li>
                        <li class="nav-item"> <a href="{{ url('/admin/country') }}" class="nav-link {{ (Request::segment('2') == 'country') ? 'active': ''}}"> <i class="nav-icon bi bi-circle"></i>
                                <p>Country</p>
                            </a> </li>
                        <li class="nav-item"> <a href="{{ url('/admin/speciality') }}" class="nav-link {{ (Request::segment('2') == 'speciality') ? 'active': ''}}"> <i class="nav-icon bi bi-circle"></i>
                            <p>Speciality</p>
                        </a> </li>
                        <li class="nav-item"> <a href="{{ url('/admin/appointment') }}" class="nav-link {{ (Request::segment('2') == 'appointment') ? 'active': ''}}"> <i class="nav-icon bi bi-circle"></i>
                            <p>Appointment</p>
                        </a> </li> 
                         <li class="nav-item"> <a href="{{ url('/admin/payment-log') }}" class="nav-link {{ (Request::segment('2') == 'payment-log') ? 'active': ''}}"> <i class="nav-icon bi bi-circle"></i>
                            <p>Payment-Logs</p>
                        </a> </li> 
                                                                        
                                                                           <li class="nav-item">
                            <a href="{{ url('/admin/settings') }}" class="nav-link {{ (Request::segment('2') == 'settings') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-gear"></i>
                                <p>Settings</p>
                            </a>
                        </li>                                                       
                                                                                         
                    </ul> <!--end::Sidebar Menu-->
                </nav>
            </div> <!--end::Sidebar Wrapper-->
        </aside>