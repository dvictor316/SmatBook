<script>
    /**
     * Global Print Handler
     * Used for Super Admin and Manager reports
     */
    function printPage() {
        if (typeof window.smartProbookTriggerPrint === 'function') {
            window.smartProbookTriggerPrint();
            return;
        }

        window.print();
    }

    document.addEventListener('keydown', function(event) {
        if ((event.ctrlKey || event.metaKey) && event.key === 'p') {
            event.preventDefault();
            printPage();
        }
    });
</script>
