<?php if(isLogged()): ?>
  </main>
</div>
</div>
<?php endif; ?>
<script src="<?= BASE ?>/assets/js/main.js"></script>
<?php if(!empty($extraJs)) echo $extraJs; ?>
</body>
</html>
