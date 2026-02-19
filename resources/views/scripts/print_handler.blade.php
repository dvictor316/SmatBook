<script>
    /**
     * Global Print Handler
     * Used for Super Admin and Manager reports
     */
    function printPage() {
        // Hide sidebar and navbar if necessary for a clean print
        window.print();
    }

    document.addEventListener('keydown', function(event) {
        if ((event.ctrlKey || event.metaKey) && event.key === 'p') {
            event.preventDefault();
            printPage();
        }
    });
</script>