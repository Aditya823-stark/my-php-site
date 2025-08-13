<?php
include('connect/db.php');
include('connect/fun.php');

$db = (new connect())->myconnect();
$fun = new fun($db);

// Create admin_users table if not exists
$create_users_table = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('Super Admin', 'Admin', 'Manager', 'Agent', 'Staff') DEFAULT 'Staff',
    permissions JSON,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    FOREIGN KEY (created_by) REFERENCES admin_users(id)
)";
mysqli_query($db, $create_users_table);

// Create user_sessions table for session management
$create_sessions_table = "CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES admin_users(id)
)";
mysqli_query($db, $create_sessions_table);

// Default permissions structure
$default_permissions = [
    'dashboard' => ['view'],
    'passengers' => ['view', 'create', 'edit', 'delete'],
    'trains' => ['view', 'create', 'edit', 'delete'],
    'schedules' => ['view', 'create', 'edit', 'delete'],
    'bookings' => ['view', 'create', 'edit', 'cancel'],
    'reports' => ['view', 'export'],
    'users' => ['view', 'create', 'edit', 'delete'],
    'settings' => ['view', 'edit']
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $username = mysqli_real_escape_string($db, $_POST['username']);
        $email = mysqli_real_escape_string($db, $_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $full_name = mysqli_real_escape_string($db, $_POST['full_name']);
        $role = mysqli_real_escape_string($db, $_POST['role']);
        
        // Set permissions based on role
        $permissions = [];
        switch ($role) {
            case 'Super Admin':
                foreach ($default_permissions as $module => $actions) {
                    $permissions[$module] = $actions;
                }
                break;
            case 'Admin':
                $permissions = [
                    'dashboard' => ['view'],
                    'passengers' => ['view', 'create', 'edit', 'delete'],
                    'trains' => ['view', 'create', 'edit'],
                    'schedules' => ['view', 'create', 'edit'],
                    'bookings' => ['view', 'create', 'edit', 'cancel'],
                    'reports' => ['view', 'export'],
                    'users' => ['view', 'create', 'edit']
                ];
                break;
            case 'Manager':
                $permissions = [
                    'dashboard' => ['view'],
                    'passengers' => ['view', 'create', 'edit'],
                    'trains' => ['view'],
                    'schedules' => ['view'],
                    'bookings' => ['view', 'create', 'edit'],
                    'reports' => ['view', 'export']
                ];
                break;
            case 'Agent':
                $permissions = [
                    'dashboard' => ['view'],
                    'passengers' => ['view', 'create', 'edit'],
                    'bookings' => ['view', 'create'],
                    'reports' => ['view']
                ];
                break;
            case 'Staff':
                $permissions = [
                    'dashboard' => ['view'],
                    'passengers' => ['view', 'create'],
                    'bookings' => ['view', 'create']
                ];
                break;
        }
        
        $permissions_json = json_encode($permissions);
        
        $sql = "INSERT INTO admin_users (username, email, password, full_name, role, permissions) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "ssssss", $username, $email, $password, $full_name, $role, $permissions_json);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "User added successfully!";
        } else {
            $error_msg = "Error adding user: " . mysqli_error($db);
        }
    }
    
    if (isset($_POST['update_status'])) {
        $user_id = (int)$_POST['user_id'];
        $is_active = (int)$_POST['is_active'];
        
        mysqli_query($db, "UPDATE admin_users SET is_active = $is_active WHERE id = $user_id");
        $success_msg = "User status updated successfully!";
    }
}

// Get all users
$users_query = "SELECT * FROM admin_users ORDER BY created_at DESC";
$users = mysqli_query($db, $users_query);

// Get user statistics
$stats = [
    'total_users' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM admin_users"))['count'],
    'active_users' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM admin_users WHERE is_active = 1"))['count'],
    'super_admins' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM admin_users WHERE role = 'Super Admin'"))['count'],
    'recent_logins' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM admin_users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)"))['count']
];
?>

<?php include 'inlude/header.php'; ?>
<?php include 'inlude/nav.php'; ?>
<?php include 'inlude/sidebar.php'; ?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">User Management & Permissions</h3>
            <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                    <a href="index.php">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">System Management</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">User Management</a>
                </li>
            </ul>
        </div>

        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> <?= $success_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> <?= $error_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-primary bubble-shadow-small">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Users</p>
                                    <h4 class="card-title"><?= $stats['total_users'] ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-success bubble-shadow-small">
                                    <i class="fas fa-user-check"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Active Users</p>
                                    <h4 class="card-title"><?= $stats['active_users'] ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-warning bubble-shadow-small">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Super Admins</p>
                                    <h4 class="card-title"><?= $stats['super_admins'] ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-info bubble-shadow-small">
                                    <i class="fas fa-sign-in-alt"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Recent Logins</p>
                                    <h4 class="card-title"><?= $stats['recent_logins'] ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Add User Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-user-plus"></i> Add New User
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" name="username" id="username" class="form-control" placeholder="Enter username" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control" placeholder="Enter email address" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" name="full_name" id="full_name" class="form-control" placeholder="Enter full name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="role">User Role</label>
                                <select name="role" id="role" class="form-select" required>
                                    <option value="">Select Role</option>
                                    <option value="Super Admin">Super Admin</option>
                                    <option value="Admin">Admin</option>
                                    <option value="Manager">Manager</option>
                                    <option value="Agent">Agent</option>
                                    <option value="Staff">Staff</option>
                                </select>
                            </div>
                            
                            <div id="role-permissions" class="mt-3" style="display: none;">
                                <h6>Role Permissions Preview</h6>
                                <div id="permissions-list" class="small text-muted"></div>
                            </div>
                            
                            <div class="card-action">
                                <button type="submit" name="add_user" class="btn btn-primary">
                                    <i class="fa fa-plus"></i> Add User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Role Hierarchy Info -->
                <div class="card mt-3">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-info-circle"></i> Role Hierarchy
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="role-item mb-2">
                            <span class="badge badge-danger">Super Admin</span>
                            <small class="d-block text-muted">Full system access</small>
                        </div>
                        <div class="role-item mb-2">
                            <span class="badge badge-warning">Admin</span>
                            <small class="d-block text-muted">Manage users & system</small>
                        </div>
                        <div class="role-item mb-2">
                            <span class="badge badge-info">Manager</span>
                            <small class="d-block text-muted">Manage operations</small>
                        </div>
                        <div class="role-item mb-2">
                            <span class="badge badge-success">Agent</span>
                            <small class="d-block text-muted">Handle bookings</small>
                        </div>
                        <div class="role-item">
                            <span class="badge badge-secondary">Staff</span>
                            <small class="d-block text-muted">Basic operations</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-list"></i> System Users
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($users) === 0): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> No users found. Add some users to get started.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table id="users-table" class="display table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 25%">User</th>
                                            <th style="width: 15%">Role</th>
                                            <th style="width: 10%">Status</th>
                                            <th style="width: 20%">Last Login</th>
                                            <th style="width: 15%">Created</th>
                                            <th style="width: 15%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($user = mysqli_fetch_assoc($users)): ?>
                                            <tr>
                                                <td>
                                                    <div class="avatar avatar-sm">
                                                        <span class="avatar-title rounded-circle border border-white bg-primary text-white">
                                                            <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                                                        </span>
                                                    </div>
                                                    <div class="info-user ms-3">
                                                        <div class="username"><?= htmlspecialchars($user['full_name']) ?></div>
                                                        <div class="status">@<?= htmlspecialchars($user['username']) ?></div>
                                                        <div class="status"><?= htmlspecialchars($user['email']) ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= $user['role'] == 'Super Admin' ? 'danger' : ($user['role'] == 'Admin' ? 'warning' : ($user['role'] == 'Manager' ? 'info' : ($user['role'] == 'Agent' ? 'success' : 'secondary'))) ?>">
                                                        <?= htmlspecialchars($user['role']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= $user['is_active'] ? 'success' : 'danger' ?>">
                                                        <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?= $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : '<span class="text-muted">Never</span>' ?>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                                <td>
                                                    <div class="form-button-action">
                                                        <button type="button" class="btn btn-link btn-info btn-lg" 
                                                                data-bs-toggle="tooltip" title="View Permissions"
                                                                onclick="viewPermissions(<?= $user['id'] ?>, '<?= htmlspecialchars($user['full_name']) ?>', '<?= htmlspecialchars($user['permissions']) ?>')">
                                                            <i class="fa fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-link btn-warning btn-lg" data-bs-toggle="tooltip" title="Edit User">
                                                            <i class="fa fa-edit"></i>
                                                        </button>
                                                        <form method="post" style="display: inline;">
                                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                            <input type="hidden" name="is_active" value="<?= $user['is_active'] ? 0 : 1 ?>">
                                                            <button type="submit" name="update_status" 
                                                                    class="btn btn-link btn-<?= $user['is_active'] ? 'danger' : 'success' ?> btn-lg" 
                                                                    data-bs-toggle="tooltip" 
                                                                    title="<?= $user['is_active'] ? 'Deactivate' : 'Activate' ?> User">
                                                                <i class="fa fa-<?= $user['is_active'] ? 'ban' : 'check' ?>"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Permissions Modal -->
<div class="modal fade" id="permissionsModal" tabindex="-1" aria-labelledby="permissionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="permissionsModalLabel">User Permissions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="permissions-content">
                    <!-- Permissions will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'inlude/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#users-table').DataTable({
        "pageLength": 10,
        "lengthChange": false,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "order": [[ 4, "desc" ]]
    });

    // Show permissions preview when role is selected
    $('#role').change(function() {
        var role = $(this).val();
        var permissions = getRolePermissions(role);
        
        if (permissions.length > 0) {
            $('#permissions-list').html(permissions.join('<br>'));
            $('#role-permissions').show();
        } else {
            $('#role-permissions').hide();
        }
    });
});

function getRolePermissions(role) {
    var permissions = [];
    
    switch (role) {
        case 'Super Admin':
            permissions = [
                '✓ Full system access',
                '✓ Manage all users',
                '✓ System settings',
                '✓ All reports and analytics',
                '✓ Database management'
            ];
            break;
        case 'Admin':
            permissions = [
                '✓ Manage passengers and bookings',
                '✓ Manage trains and schedules',
                '✓ Create/edit users (except Super Admin)',
                '✓ View all reports',
                '✓ Export data'
            ];
            break;
        case 'Manager':
            permissions = [
                '✓ View and manage passengers',
                '✓ View trains and schedules',
                '✓ Handle bookings and cancellations',
                '✓ View reports',
                '✓ Export data'
            ];
            break;
        case 'Agent':
            permissions = [
                '✓ Create and edit passenger bookings',
                '✓ View passenger information',
                '✓ Basic reporting',
                '✓ Handle customer inquiries'
            ];
            break;
        case 'Staff':
            permissions = [
                '✓ Create passenger bookings',
                '✓ View basic passenger info',
                '✓ Limited dashboard access'
            ];
            break;
    }
    
    return permissions;
}

function viewPermissions(userId, userName, permissionsJson) {
    $('#permissionsModalLabel').text(`Permissions - ${userName}`);
    
    try {
        var permissions = JSON.parse(permissionsJson);
        var content = '<div class="row">';
        
        for (var module in permissions) {
            content += `<div class="col-md-6 mb-3">
                <h6 class="text-capitalize">${module.replace('_', ' ')}</h6>
                <div class="permissions-list">`;
            
            permissions[module].forEach(function(action) {
                content += `<span class="badge badge-outline-primary me-1">${action}</span>`;
            });
            
            content += '</div></div>';
        }
        
        content += '</div>';
        $('#permissions-content').html(content);
    } catch (e) {
        $('#permissions-content').html('<div class="alert alert-warning">Error loading permissions</div>');
    }
    
    $('#permissionsModal').modal('show');
}
</script>
