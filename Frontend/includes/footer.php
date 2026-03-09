</div><!-- /container -->
</div><!-- /main-wrap -->

<footer style="border-top:1px solid rgba(134,113,91,0.2); padding:1.5rem 0; margin-top:3rem;">
    <div class="container text-center" style="color:var(--text-muted); font-size:0.82rem; letter-spacing:0.08em;">
        <span style="font-family:'IM Fell English',serif; font-size:1rem; color:var(--umber);">Noetic.</span>
        &nbsp;—&nbsp; a society of readers &nbsp;—&nbsp;
        <span style="font-style:italic;"><?php echo date('Y'); ?></span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- DEBUG: RabbitMQ RPC Calls -->
<script>
(function() {
    const DEBUG_DATA = <?php echo json_encode($_DEBUG_LOG ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>;
    
    console.group('%c[RabbitMQ Debug Log]', 'color: #86715B; font-weight: bold; font-size: 14px;');
    console.log('%cTotal RPC calls: ' + DEBUG_DATA.length, 'color: #DCBCCE;');
    
    DEBUG_DATA.forEach(function(item, index) {
        const hasError = item.error || (item.response && item.response.success === false);
        const style = hasError ? 'color: #ff6b6b; font-weight: bold;' : 'color: #69db7c;';
        const icon = hasError ? '[FAIL]' : '[OK]';
        
        console.groupCollapsed(icon + ' [' + (index + 1) + '] ' + item.action);
        console.log('%cRequest:', 'color: #74c0fc; font-weight: bold;', item.request);
        if (item.error) {
            console.log('%cError:', 'color: #ff6b6b; font-weight: bold;', item.error);
        } else {
            console.log('%cResponse:', style, item.response);
            if (item.raw) {
                console.log('%cRaw:', 'color: #868e96;', item.raw);
            }
        }
        console.groupEnd();
    });
    
    // Highlight any failed calls
    const failed = DEBUG_DATA.filter(function(item) {
        return item.error || (item.response && item.response.success === false);
    });
    if (failed.length > 0) {
        console.warn('%c[WARNING] ' + failed.length + ' RPC call(s) failed or returned success:false', 'color: #ffa94d; font-weight: bold;');
    }
    
    console.groupEnd();
})();
</script>
</body>
</html>