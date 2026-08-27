</div>
</main>
<footer class="footer">
	<div class='navbar navbar-expand-lg navbar-dark bg-dark py-0 '>
		<div class="mx-auto">
			<span class="navbar-text py-1">
				<?php if ('0' == $Config->read('hide_copyright')) { ?>
					&copy; <?php echo date('Y'); ?>&nbsp;<?php echo __('BY'); ?> reloxx13 -
				<?php } ?>
				<a href='https://github.com/cyberrep/hassio-openbkadmin' target='_blank' rel='noopener noreferrer'>OpenBKAdmin GitHub</a>
				-
				<a href='https://github.com/openshwprojects/OpenBK7231T_App' target='_blank' rel='noopener noreferrer'>OpenBeken GitHub</a>
				- Version 0.7.1
			</span>
		</div>
	</div>
</footer>
<div class="modal fade touch-confirm-modal" id="touchToggleConfirmModal" tabindex="-1" aria-labelledby="touchToggleConfirmModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
		<div class="modal-content">
			<div class="modal-header border-0 pb-0">
				<h5 class="modal-title toggle-confirm-modal-title" id="touchToggleConfirmModalLabel"><?php echo __('CONFIRM_SWITCHES', 'DEVICES'); ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo __('CLOSE'); ?>"></button>
			</div>
			<div class="modal-body toggle-confirm-modal-body pt-2"></div>
			<div class="modal-footer border-0">
				<button type="button" class="btn btn-outline-secondary btn-lg toggle-confirm-modal-cancel" data-bs-dismiss="modal"><?php echo __('CANCEL'); ?></button>
				<button type="button" class="btn btn-primary btn-lg toggle-confirm-modal-confirm"><?php echo __('CONFIRM'); ?></button>
			</div>
		</div>
	</div>
</div>
</body>
</html>
