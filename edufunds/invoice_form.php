<form action="create_invoice.php" method="POST">
    <label for="student_id">Student:</label>
    <select name="student_id" id="student_id" required>
        <?php
        $result = $conn->query("SELECT id, name FROM students");
        while ($row = $result->fetch_assoc()) {
            echo "<option value='{$row['id']}'>{$row['name']}</option>";
        }
        ?>
    </select>

    <label for="amount">Amount:</label>
    <input type="number" name="amount" id="amount" required>

    <label for="description">Description:</label>
    <input type="text" name="description" id="description">

    <button type="submit">Create Invoice</button>
</form>
