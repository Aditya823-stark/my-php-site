 <!-- Sidebar -->
      <div class="sidebar" data-background-color="dark">
        <div class="sidebar-logo">
          <!-- Logo Header -->
          <div class="logo-header" data-background-color="dark">
            <a href="index.html" class="logo">
              <img
                src="assets/img/kaiadmin/logo_light.svg"
                alt="navbar brand"
                class="navbar-brand"
                height="20"
              />
            </a>
            <div class="nav-toggle">
              <button class="btn btn-toggle toggle-sidebar">
                <i class="gg-menu-right"></i>
              </button>
              <button class="btn btn-toggle sidenav-toggler">
                <i class="gg-menu-left"></i>
              </button>
            </div>
            <button class="topbar-toggler more">
              <i class="gg-more-vertical-alt"></i>
            </button>
          </div>
          <!-- End Logo Header -->
        </div>
        <div class="sidebar-wrapper scrollbar scrollbar-inner">
          <div class="sidebar-content">
            <ul class="nav nav-secondary">
              <li class="nav-item active">
                <a
                  data-bs-toggle="collapse"
                  href="#dashboard"
                  class="collapsed"
                  aria-expanded="false"
                >
                  <i class="fas fa-home"></i>
                  <p>Dashboard</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="dashboard">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="dashboard.php">
                        <span class="sub-item">Main Dashboard</span>
                      </a>
                    </li>
<li>
  <a href="live_train.php?train=12627">
    <span class="sub-item">Live Train Tracking</span>
  </a>
</li>

                  </ul>
                </div>
              </li>
              <!-- Railway Management Section -->
              <li class="nav-section">
                <span class="sidebar-mini-icon">
                  <i class="fa fa-train"></i>
                </span>
                <h4 class="text-section">Railway Management</h4>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#passengers">
                  <i class="fas fa-users"></i>
                  <p>Passengers</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="passengers">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="passenger_booking.php">
                        <span class="sub-item">Book Tickets</span>
                      </a>
                    </li>
                    <li>
                      <a href="passenger_management.php">
                        <span class="sub-item">Manage Passengers</span>
                      </a>
                    </li>
                    <li>
                      <a href="seats/passenger_details.php">
                        <span class="sub-item">Seat Booking</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#trains">
                  <i class="fas fa-train"></i>
                  <p>Trains & Routes</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="trains">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="train_management.php">
                        <span class="sub-item">Manage Trains</span>
                      </a>
                    </li>
                    <li>
                      <a href="train_schedule.php">
                        <span class="sub-item">Train Schedules</span>
                      </a>
                    </li>
                    <li>
                      <a href="seat_management.php">
                        <span class="sub-item">Seat Management</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#operations">
                  <i class="fas fa-cogs"></i>
                  <p>Operations</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="operations">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="ticket_cancellation.php">
                        <span class="sub-item">Cancellations & Refunds</span>
                      </a>
                    </li>
                    <li>
                      <a href="maintenance_logs.php">
                        <span class="sub-item">Train Maintenance</span>
                      </a>
                    </li>
                    <li>
                      <a href="notifications.php">
                        <span class="sub-item">Notifications</span>
                      </a>
                    </li>
                    <li>
                      <a href="notification_system.php">
                        <span class="sub-item">SMS/Email Notifications</span>
                      </a>
                    </li>
                    <li>
                      <a href="dynamic_pricing.php">
                        <span class="sub-item">Dynamic Pricing</span>
                      </a>
                    </li>
                    <li>
                      <a href="feedback_system.php">
                        <span class="sub-item">Feedback System</span>
                      </a>
                    </li>
                    <li>
                      <a href="payment_integration.php">
                        <span class="sub-item">Payment Gateway</span>
                      </a>
                    </li>
                    <li>
                      <a href="gps_tracking.php">
                        <span class="sub-item">GPS Tracking</span>
                      </a>
                    </li>
                    <li>
                      <a href="weather_integration.php">
                        <span class="sub-item">Weather Integration</span>
                      </a>
                    </li>
                    <li>
                      <a href="loyalty_program.php">
                        <span class="sub-item">Loyalty Program</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#system">
                  <i class="fas fa-shield-alt"></i>
                  <p>System Management</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="system">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="user_management.php">
                        <span class="sub-item">User Management</span>
                      </a>
                    </li>
                    <li>
                      <a href="system_settings.php">
                        <span class="sub-item">System Settings</span>
                      </a>
                    </li>
                    <li>
                      <a href="backup_restore.php">
                        <span class="sub-item">Backup & Restore</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#reports">
                  <i class="fas fa-chart-line"></i>
                  <p>Reports</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="reports">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="booking_reports.php">
                        <span class="sub-item">Booking Reports</span>
                      </a>
                    </li>
                    <li>
                      <a href="revenue_reports.php">
                        <span class="sub-item">Revenue Reports</span>
                      </a>
                    </li>
                    <li>
                      <a href="passenger_reports.php">
                        <span class="sub-item">Passenger Reports</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="nav-section">
                <span class="sidebar-mini-icon">
                  <i class="fa fa-ellipsis-h"></i>
                </span>
                <h4 class="text-section">Components</h4>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#base">
                  <i class="fas fa-layer-group"></i>
                  <p>Base</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="base">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="components/avatars.html">
                        <span class="sub-item">Avatars</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/buttons.html">
                        <span class="sub-item">Buttons</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/gridsystem.html">
                        <span class="sub-item">Grid System</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/panels.html">
                        <span class="sub-item">Panels</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/notifications.html">
                        <span class="sub-item">Notifications</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/sweetalert.html">
                        <span class="sub-item">Sweet Alert</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/font-awesome-icons.html">
                        <span class="sub-item">Font Awesome Icons</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/simple-line-icons.html">
                        <span class="sub-item">Simple Line Icons</span>
                      </a>
                    </li>
                    <li>
                      <a href="components/typography.html">
                        <span class="sub-item">Typography</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#sidebarLayouts">
                  <i class="fas fa-th-list"></i>
                  <p>Sidebar Layouts</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="sidebarLayouts">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="sidebar-style-2.html">
                        <span class="sub-item">Sidebar Style 2</span>
                      </a>
                    </li>
                    <li>
                      <a href="icon-menu.html">
                        <span class="sub-item">Icon Menu</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#forms">
                  <i class="fas fa-pen-square"></i>
                  <p>Forms</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="forms">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="forms/forms.html">
                        <span class="sub-item">Basic Form</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#tables">
                  <i class="fas fa-table"></i>
                  <p>Tables</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="tables">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="tables/tables.html">
                        <span class="sub-item">Basic Table</span>
                      </a>
                    </li>
                    <li>
                      <a href="tables/datatables.html">
                        <span class="sub-item">Datatables</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#maps">
                  <i class="fas fa-map-marker-alt"></i>
                  <p>Maps</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="maps">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="maps/googlemaps.html">
                        <span class="sub-item">Google Maps</span>
                      </a>
                    </li>
                    <li>
                      <a href="maps/jsvectormap.html">
                        <span class="sub-item">Jsvectormap</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#charts">
                  <i class="far fa-chart-bar"></i>
                  <p>Charts</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="charts">
                  <ul class="nav nav-collapse">
                    <li>
                      <a href="charts/charts.html">
                        <span class="sub-item">Chart Js</span>
                      </a>
                    </li>
                    <li>
                      <a href="charts/sparkline.html">
                        <span class="sub-item">Sparkline</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
              <li class="nav-item">
                <a href="widgets.html">
                  <i class="fas fa-desktop"></i>
                  <p>Widgets</p>
                  <span class="badge badge-success">4</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="../../documentation/index.html">
                  <i class="fas fa-file"></i>
                  <p>Documentation</p>
                  <span class="badge badge-secondary">1</span>
                </a>
              </li>
              <li class="nav-item">
                <a data-bs-toggle="collapse" href="#submenu">
                  <i class="fas fa-bars"></i>
                  <p>Menu Levels</p>
                  <span class="caret"></span>
                </a>
                <div class="collapse" id="submenu">
                  <ul class="nav nav-collapse">
                    <li>
                      <a data-bs-toggle="collapse" href="#subnav1">
                        <span class="sub-item">Level 1</span>
                        <span class="caret"></span>
                      </a>
                      <div class="collapse" id="subnav1">
                        <ul class="nav nav-collapse subnav">
                          <li>
                            <a href="#">
                              <span class="sub-item">Level 2</span>
                            </a>
                          </li>
                          <li>
                            <a href="#">
                              <span class="sub-item">Level 2</span>
                            </a>
                          </li>
                        </ul>
                      </div>
                    </li>
                    <li>
                      <a data-bs-toggle="collapse" href="#subnav2">
                        <span class="sub-item">Level 1</span>
                        <span class="caret"></span>
                      </a>
                      <div class="collapse" id="subnav2">
                        <ul class="nav nav-collapse subnav">
                          <li>
                            <a href="#">
                              <span class="sub-item">Level 2</span>
                            </a>
                          </li>
                        </ul>
                      </div>
                    </li>
                    <li>
                      <a href="#">
                        <span class="sub-item">Level 1</span>
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <!-- End Sidebar -->