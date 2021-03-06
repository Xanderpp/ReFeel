<div class="modal fade" id="modalContInfo" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content" style='border-radius: 30px 30px 25px 25px;'>
			<div class="modal-header bg-danger" style='border-radius: 25px 25px 0px 0px;'>
				<i class="fa fa-phone p-2" style="color: white;"></i>
				<h5 class="modal-title text-white" style=" vertical-align: middle;">Personal Information</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true" class='text-white'>&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form method="POST" id="frmClientContInfo">
					<!-- Occupation -->
					<div class="form-group">
						<label for="lblContNo" class="col-form-label">Contact Number</label>
						<input type="text" class="form-control" name="txtNewContNo" value="<?php echo $varContNo;?>" required="required" />
					</div>
					<div class="form-group">
						<label for="lblEmail" class="col-form-label">E-mail Address</label>
						<input type="text" class="form-control" name="txtNewEmail" value="<?php echo $varEmail;?>" required="required" />
					</div>
					
					<div class="modal-footer pb-1">
						<input type='hidden' name='btnAct' value='Contact' />
						<input type='submit' value='Update' id='btnCont' class='btn btn-outline-danger' />
					</div>
				</form>
			</div>
		</div>
	</div>
</div>