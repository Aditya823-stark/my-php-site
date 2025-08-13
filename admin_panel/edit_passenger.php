<?php
include("../connect/db.php");
include("../connect/fun.php");

$db = (new connect())->myconnect();
$fun = new fun($db);

$id = (int)($_GET['id'] ?? 0);
$passenger = $fun->get_passenger_by_id($id);

if (!$passenger) {
    echo "<h3 class='text-danger'>Passenger not found!</h3>";
    exit;
}

// Update logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => $_POST['name'],
        'age' => $_POST['age'],
        'gender' => $_POST['gender'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'],
        'class_type' => $_POST['class_type'],
    ];

    $stmt = $db->prepare("UPDATE passengers SET name=?, age=?, gender=?, email=?, phone=?, class_type=? WHERE id=?");
    $stmt->bind_param("sissssi", $data['name'], $data['age'], $data['gender'], $data['email'], $data['phone'], $data['class_type'], $id);

    if ($stmt->execute()) {
        header("Location: admin_panel_view.php?updated=1");
        exit;
    } else {
        echo "<h4 class='text-danger'>❌ Failed to update. Try again.</h4>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Passenger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4 text-center">Edit Passenger - <?= htmlspecialchars($passenger['name']) ?></h2>

    <form method="POST" class="shadow p-4 bg-white rounded">
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($passenger['name']) ?>" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Age</label>
            <input type="number" name="age" value="<?= $passenger['age'] ?>" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-control" required>
                <option value="Male" <?= $passenger['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= $passenger['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                <option value="Other" <?= $passenger['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($passenger['email']) ?>" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($passenger['phone']) ?>" class="form-control" required>
        </div>
        <div class="mb-4">
            <label class="form-label">Class Type</label>
            <select name="class_type" class="form-control" required>
                <option value="General" <?= $passenger['class_type'] == 'General' ? 'selected' : '' ?>>General</option>
                <option value="Sleeper" <?= $passenger['class_type'] == 'Sleeper' ? 'selected' : '' ?>>Sleeper</option>
                <option value="AC" <?= $passenger['class_type'] == 'AC' ? 'selected' : '' ?>>AC</option>
                <option value="AC Tier 1" <?= $passenger['class_type'] == 'AC Tier 1' ? 'selected' : '' ?>>AC Tier 1</option>
                <option value="AC Tier 2" <?= $passenger['class_type'] == 'AC Tier 2' ? 'selected' : '' ?>>AC Tier 2</option>
                <option value="AC Tier 3" <?= $passenger['class_type'] == 'AC Tier 3' ? 'selected' : '' ?>>AC Tier 3</option>
            </select>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-success">Update Passenger</button>
            <a href="admin_panel_view.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
</body>
</html>
