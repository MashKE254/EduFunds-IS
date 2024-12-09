async function updateDashboard() {
    const response = await fetch('update_dashboard.php');
    const data = await response.json();

    document.getElementById('totalIncome').textContent = '$' + data.totalIncome.toFixed(2);
    document.getElementById('totalExpenses').textContent = '$' + data.totalExpenses.toFixed(2);
}

updateDashboard();