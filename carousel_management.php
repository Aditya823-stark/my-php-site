<?php
include('auth_check.php');
include('connect/db.php');
$db = (new connect())->myconnect();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = $_POST['title'];
                $description = $_POST['description'];
                $image = $_POST['image'];
                $button_text = $_POST['button_text'];
                $button_link = $_POST['button_link'];
                $sort_order = $_POST['sort_order'];
                
                $insert_sql = "INSERT INTO carousel_slides (title, description, image, button_text, button_link, sort_order) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($db, $insert_sql);
                mysqli_stmt_bind_param($stmt, "sssssi", $title, $description, $image, $button_text, $button_link, $sort_order);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "✅ Carousel slide added successfully!";
                } else {
                    $error_message = "❌ Error adding slide: " . mysqli_error($db);
                }
                mysqli_stmt_close($stmt);
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $title = $_POST['title'];
                $description = $_POST['description'];
                $image = $_POST['image'];
                $button_text = $_POST['button_text'];
                $button_link = $_POST['button_link'];
                $sort_order = $_POST['sort_order'];
                $status = $_POST['status'];
                
                $update_sql = "UPDATE carousel_slides SET title = ?, description = ?, image = ?, button_text = ?, button_link = ?, sort_order = ?, status = ? WHERE id = ?";
                $stmt = mysqli_prepare($db, $update_sql);
                mysqli_stmt_bind_param($stmt, "sssssisi", $title, $description, $image, $button_text, $button_link, $sort_order, $status, $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "✅ Carousel slide updated successfully!";
                } else {
                    $error_message = "❌ Error updating slide: " . mysqli_error($db);
                }
                mysqli_stmt_close($stmt);
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $delete_sql = "DELETE FROM carousel_slides WHERE id = ?";
                $stmt = mysqli_prepare($db, $delete_sql);
                mysqli_stmt_bind_param($stmt, "i", $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "✅ Carousel slide deleted successfully!";
                } else {
                    $error_message = "❌ Error deleting slide: " . mysqli_error($db);
                }
                mysqli_stmt_close($stmt);
                break;
        }
    }
}

// Get all carousel slides
$slides_query = "SELECT * FROM carousel_slides ORDER BY sort_order ASC, id ASC";
$slides_result = mysqli_query($db, $slides_query);
$slides = [];
while ($slide = mysqli_fetch_assoc($slides_result)) {
    $slides[] = $slide;
}

// Get customization for styling
$customization_query = "SELECT * FROM website_customization WHERE id = 1";
$customization_result = mysqli_query($db, $customization_query);
$customization = mysqli_fetch_assoc($customization_result);

if (!$customization) {
    $customization = ['primary_color' => '#1e3a8a', 'secondary_color' => '#f97316', 'accent_color' => '#dc2626'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carousel Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: <?php echo $customization['primary_color']; ?>;
            --secondary-color: <?php echo $customization['secondary_color']; ?>;
            --accent-color: <?php echo $customization['accent_color']; ?>;
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        
        .carousel-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .slide-preview {
            background-size: cover;
            background-position: center;
            height: 200px;
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .slide-preview::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(30, 58, 138, 0.8), rgba(249, 115, 22, 0.6));
        }
        
        .slide-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: white;
            z-index: 2;
        }
        
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .slide-item {
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .slide-item:hover {
            border-color: var(--secondary-color);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-inactive {
            background: #fef2f2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-images"></i> Carousel Management</h1>
                    <p class="mb-0">Manage carousel slides for your IRCTC website</p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="admin_customization.php" class="btn btn-light">
                        <i class="fas fa-arrow-left"></i> Back to Customization
                    </a>
                    <a href="index/index.php" class="btn btn-outline-light ms-2" target="_blank">
                        <i class="fas fa-external-link-alt"></i> View Website
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="carousel-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3><i class="fas fa-list"></i> Current Slides</h3>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSlideModal">
                            <i class="fas fa-plus"></i> Add New Slide
                        </button>
                    </div>

                    <?php if (empty($slides)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-images fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No carousel slides found</h5>
                        <p class="text-muted">Add your first slide to get started</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSlideModal">
                            <i class="fas fa-plus"></i> Add First Slide
                        </button>
                    </div>
                    <?php else: ?>
                    <?php foreach ($slides as $slide): ?>
                    <div class="slide-item">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="slide-preview" style="background-image: url('<?php echo $slide['image']; ?>')">
                                    <div class="slide-content">
                                        <h6><?php echo htmlspecialchars($slide['title']); ?></h6>
                                        <small><?php echo htmlspecialchars($slide['description']); ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5><?php echo htmlspecialchars($slide['title']); ?></h5>
                                    <div>
                                        <span class="status-badge status-<?php echo $slide['status']; ?>">
                                            <?php echo ucfirst($slide['status']); ?>
                                        </span>
                                        <span class="badge bg-secondary ms-2">Order: <?php echo $slide['sort_order']; ?></span>
                                    </div>
                                </div>
                                <p class="text-muted mb-3"><?php echo htmlspecialchars($slide['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted">
                                            <i class="fas fa-link"></i> <?php echo htmlspecialchars($slide['button_text']); ?> 
                                            → <?php echo htmlspecialchars($slide['button_link']); ?>
                                        </small>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary me-2" onclick="editSlide(<?php echo htmlspecialchars(json_encode($slide)); ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this slide?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $slide['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="carousel-card">
                    <h4><i class="fas fa-info-circle"></i> Instructions</h4>
                    <div class="alert alert-info">
                        <h6>How to manage carousel slides:</h6>
                        <ul class="mb-0">
                            <li><strong>Add:</strong> Click "Add New Slide" to create a slide</li>
                            <li><strong>Edit:</strong> Click "Edit" to modify existing slides</li>
                            <li><strong>Order:</strong> Use sort_order to control slide sequence</li>
                            <li><strong>Status:</strong> Set to "active" to show on website</li>
                            <li><strong>Images:</strong> Use full URLs for images</li>
                        </ul>
                    </div>
                </div>

                <div class="carousel-card">
                    <h4><i class="fas fa-eye"></i> Live Preview</h4>
                    <p class="text-muted">Active slides on your website:</p>
                    <div id="carouselPreview" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php 
                            $active_slides = array_filter($slides, function($slide) { return $slide['status'] === 'active'; });
                            foreach ($active_slides as $index => $slide): 
                            ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <div class="slide-preview" style="background-image: url('<?php echo $slide['image']; ?>')">
                                    <div class="slide-content">
                                        <h6><?php echo htmlspecialchars($slide['title']); ?></h6>
                                        <small><?php echo htmlspecialchars($slide['description']); ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($active_slides) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselPreview" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselPreview" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Slide Modal -->
    <div class="modal fade" id="addSlideModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Add New Carousel Slide</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sort Order</label>
                                <input type="number" class="form-control" name="sort_order" value="<?php echo count($slides) + 1; ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="url" class="form-control" name="image" placeholder="https://example.com/image.jpg" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Button Text</label>
                                <input type="text" class="form-control" name="button_text" value="Learn More" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Button Link</label>
                                <input type="text" class="form-control" name="button_link" value="#booking" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Slide</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Slide Modal -->
    <div class="modal fade" id="editSlideModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Carousel Slide</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editSlideForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" class="form-control" name="title" id="edit_title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sort Order</label>
                                <input type="number" class="form-control" name="sort_order" id="edit_sort_order" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="url" class="form-control" name="image" id="edit_image" required>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Button Text</label>
                                <input type="text" class="form-control" name="button_text" id="edit_button_text" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Button Link</label>
                                <input type="text" class="form-control" name="button_link" id="edit_button_link" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-control" name="status" id="edit_status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Slide</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editSlide(slide) {
            document.getElementById('edit_id').value = slide.id;
            document.getElementById('edit_title').value = slide.title;
            document.getElementById('edit_description').value = slide.description;
            document.getElementById('edit_image').value = slide.image;
            document.getElementById('edit_button_text').value = slide.button_text;
            document.getElementById('edit_button_link').value = slide.button_link;
            document.getElementById('edit_sort_order').value = slide.sort_order;
            document.getElementById('edit_status').value = slide.status;
            
            new bootstrap.Modal(document.getElementById('editSlideModal')).show();
        }
    </script>
</body>
</html>
