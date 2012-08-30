{{! Modal to show embedded video }}

<div class="modal video-modal" tabindex="-1" role="dialog" aria-labelledby="video-modal-title" aria-hidden="true">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="video-modal-title">{{title}}</h3>
	</div>
	<div class="modal-body">
		<iframe width="560" height="315" src="http://www.youtube.com/embed/{{ytid}}?autoplay=1" frameborder="0" allowfullscreen></iframe>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
	</div>
</div>