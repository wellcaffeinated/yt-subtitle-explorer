{{! Modal to show embedded video }}

<div class="modal caption-modal" tabindex="-1" role="dialog" aria-labelledby="caption-modal-title" aria-hidden="true">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="caption-modal-title">{{title}}</h3>
	</div>
	<div class="modal-body">
		<textarea>
			{{content}}
		</textarea>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
	</div>
</div>