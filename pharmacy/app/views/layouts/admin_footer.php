<?php
/**
 * admin_footer.php
 *
 * Closes an admin page: the JavaScript files and the closing HTML tags.
 */
?>
    </main>
</div>

<!-- jQuery first, then Bootstrap, then DataTables, then our own script. -->
<script src="<?= e(asset('vendor/jquery/jquery.min.js')) ?>"></script>
<script src="<?= e(asset('vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(asset('vendor/datatables/dataTables.min.js')) ?>"></script>
<script src="<?= e(asset('vendor/datatables/dataTables.bootstrap5.min.js')) ?>"></script>
<script src="<?= e(asset('js/main.js')) ?>"></script>
<script src="<?= e(asset('js/admin.js')) ?>"></script>
</body>
</html>