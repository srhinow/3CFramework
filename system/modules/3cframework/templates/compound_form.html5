<form action="<?php echo $this->action; ?>" id="<?php echo $this->id; ?>" class="tl_form <?php echo $this->class; ?>" method="<?php echo $this->method; ?>" onsubmit="<?php echo $this->onSubmit; ?>">
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="<?php echo $this->FORM_SUBMIT; ?>" />
<input type="hidden" name="FORM_FIELDS[]" value="<?php echo $this->FORM_FIELDS; ?>" />
<input type="hidden" name="REQUEST_TOKEN" value="<?php echo REQUEST_TOKEN; ?>" />

<?php if ($this->tl_error): ?>
	<p class="tl_error"><?php echo $this->tl_error; ?></p>
<?php endif;?>

<?php echo $this->_innerData; ?>

</div>

<?php echo $this->_innerButtonData; ?>
</form>
