<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">
        <img src="../images/municipal-logo.png" alt="Logo">
        <div class="location-text">Aurora, Isabela</div>
    </div>
    <ul class="sidebar-menu">
        <li class="menu-item">
            <a href="dashboard.php">
                <i class='bx bx-home'></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="view_budget_allocation.php">
                <i class='bx bx-wallet'></i>
                <span>Budget Allocation</span>
            </a>
        </li>
       
        <li class="menu-item menu-item-has-children">
            <a href="#">
                <i class='bx bx-package'></i>
                <span>Inventory</span>
                <i class='bx bx-chevron-down dropdown-icon'></i> <!-- Dropdown icon -->
            </a>
            <ul class="submenu">
                <li><a href="medicines.php">Medicines</a></li>
                <li><a href="medicine_entry.php">Medicine Entry</a></li>
            </ul>
        </li>
     
        <li class="menu-item">
            <a href="dispense.php">
            <i class='bx bx-archive-out'></i>
                <span>Dispense</span>
            </a>
        </li>
      
        <li class="menu-item">
            <a href="daily_usage_report.php">
            <i class='bx bx-file'></i>
                <span>Reports</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="alerts.php">
            <i class='bx bx-alarm-exclamation'></i>
                <span>Alerts</span>
            </a>
        </li>
        <li class="menu-item menu-item-has-children">
            <a href="#">
                <i class='bx bx-cog'></i>
                <span>Settings</span>
                <i class='bx bx-chevron-down dropdown-icon'></i> <!-- Dropdown icon -->
            </a>
            <ul class="submenu">
                
                <li><a href="change_password.php">Change Password</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </li>
    </ul>
</div>
