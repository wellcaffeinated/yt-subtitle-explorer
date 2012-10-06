<div class="modal caption-reject-modal" tabindex="-1" role="dialog" aria-labelledby="caption-reject-modal-title" aria-hidden="true">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="caption-reject-modal-title">Reject Caption Submission</h3>
	</div>
	<form action="{{action}}" method="POST">
		<div class="modal-body">
			<p>Please provide a reason for the rejection.</p>
			<select name="reason" class="rejection-reasons">
				{{#reasons}}
				<option>{{.}}</option>
				{{/reasons}}
				<option>other</option>
			</select>
			<br/>
			<input type="text" name="other_reason" class="other-reason" placeholder="other reason"/>
			<input type="hidden" name="path" value="{{path}}"/>
		</div>
		<div class="modal-footer">
			<button class="btn btn-danger" type="submit" name="action" value="reject">Reject</button>
			<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
		</div>
	</form>
</div>