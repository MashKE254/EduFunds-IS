<?php
session_start();
include('db.php');

// Ensure user is logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Get the invoice ID from the query parameters
$invoice_id = $_GET['invoice_id'] ?? null;
if (!$invoice_id) {
    die("Invoice ID is required.");
}

// Fetch the invoice details
$query = "SELECT * FROM invoices WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $invoice_id, $user_id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$invoice) {
    die("Invoice not found or you don't have permission to edit it.");
}

// Fetch the items for the invoice
$item_query = "SELECT * FROM invoice_items WHERE invoice_id = ?";
$item_stmt = $conn->prepare($item_query);
$item_stmt->bind_param("i", $invoice_id);
$item_stmt->execute();
$items = $item_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$item_stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update invoice totals
    $new_subtotal = $_POST['subtotal'];
    $new_total = $_POST['total_amount'];
    $updated_items = $_POST['items'] ?? [];

    // Update the invoice
    $update_query = "UPDATE invoices SET subtotal = ?, total = ? WHERE id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ddii", $new_subtotal, $new_total, $invoice_id, $user_id);
    $update_stmt->execute();

    // Clear old items and add new ones
    $delete_items_query = "DELETE FROM invoice_items WHERE invoice_id = ?";
    $delete_items_stmt = $conn->prepare($delete_items_query);
    $delete_items_stmt->bind_param("i", $invoice_id);
    $delete_items_stmt->execute();

    $insert_item_query = "INSERT INTO invoice_items (invoice_id, name, quantity, rate, total) VALUES (?, ?, ?, ?, ?)";
    $insert_item_stmt = $conn->prepare($insert_item_query);

    foreach ($updated_items as $item) {
        $insert_item_stmt->bind_param(
            "isidd",
            $invoice_id,
            $item['name'],
            $item['quantity'],
            $item['rate'],
            $item['total']
        );
        $insert_item_stmt->execute();
    }

    // Redirect to invoices list
    header("Location: invoices.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Invoice</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Edit Invoice</h1>
    <form method="POST">
        <h3>Invoice Items</h3>
        <table id="itemsTable">
            <thead>
                <tr>
                    <th>Product/Service</th>
                    <th>Quantity</th>
                    <th>Rate</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><input type="text" name="items[][name]" value="<?php echo htmlspecialchars($item['name']); ?>" required></td>
                    <td><input type="number" name="items[][quantity]" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="1" onchange="updateRowTotal(this)" required></td>
                    <td><input type="number" name="items[][rate]" value="<?php echo htmlspecialchars($item['rate']); ?>" min="0" onchange="updateRowTotal(this)" required></td>
                    <td class="item-total"><?php echo number_format($item['total'], 2); ?></td>
                    <td>
                        <button type="button" onclick="deleteItemRow(this)">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="button" onclick="addItemRow()">Add Item</button>

        <h3>Invoice Summary</h3>
        <p>Subtotal: <span id="subtotal"><?php echo number_format($invoice['subtotal'], 2); ?></span></p>
        <p><strong>Total: <span id="grandTotal"><?php echo number_format($invoice['total'], 2); ?></span></strong></p>

        <!-- Hidden fields for totals -->
        <input type="hidden" name="subtotal" id="subtotalInput" value="<?php echo $invoice['subtotal']; ?>">
        <input type="hidden" name="total_amount" id="totalAmountInput" value="<?php echo $invoice['total']; ?>">

        <button type="submit">Save Invoice</button>
    </form>

    <script>
        // Add a new row to the items table
        function addItemRow() {
            const tableBody = document.querySelector("#itemsTable tbody");
            const newRow = document.createElement("tr");

            newRow.innerHTML = `
                <td><input type="text" name="items[][name]" placeholder="Product/Service" required></td>
                <td><input type="number" name="items[][quantity]" value="1" min="1" onchange="updateRowTotal(this)" required></td>
                <td><input type="number" name="items[][rate]" value="0" min="0" onchange="updateRowTotal(this)" required></td>
                <td class="item-total">0.00</td>
                <td>
                    <button type="button" onclick="deleteItemRow(this)">Delete</button>
                </td>
            `;

            tableBody.appendChild(newRow);
            updateInvoiceSummary();
        }

        // Update the total for a row
        function updateRowTotal(inputElement) {
            const row = inputElement.closest("tr");
            const quantity = parseFloat(row.querySelector('[name="items[][quantity]"]').value) || 0;
            const rate = parseFloat(row.querySelector('[name="items[][rate]"]').value) || 0;

            const total = quantity * rate;
            row.querySelector(".item-total").textContent = total.toFixed(2);

            updateInvoiceSummary();
        }

        // Update the subtotal and total for the invoice
        function updateInvoiceSummary() {
            const rows = document.querySelectorAll("#itemsTable tbody tr");
            let subtotal = 0;

            rows.forEach(row => {
                const totalCell = row.querySelector(".item-total");
                subtotal += parseFloat(totalCell.textContent) || 0;
            });

            document.getElementById("subtotal").textContent = subtotal.toFixed(2);
            document.getElementById("grandTotal").textContent = subtotal.toFixed(2);
            document.getElementById("subtotalInput").value = subtotal.toFixed(2);
            document.getElementById("totalAmountInput").value = subtotal.toFixed(2);
        }

        // Delete a row from the items table
        function deleteItemRow(button) {
            const row = button.closest("tr");
            row.remove();
            updateInvoiceSummary();
        }
    </script>
</body>
</html>
