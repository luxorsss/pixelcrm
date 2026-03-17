<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-hide alerts after 3 seconds
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        if (alert) new bootstrap.Alert(alert).close();
    });
}, 3000);
</script>
</body>
</html>