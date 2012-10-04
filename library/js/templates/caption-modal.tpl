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
    <form action="http://translate.google.com" method="POST" target="_blank" class="pull-left">
      <input type="hidden" name="text" value="{{content}}" />
      <input type="hidden" name="sl" value="auto" />
      <button type="submit" class="btn ctrl-translate" title="translate" href="{{app.url_generator.generate('admin_main', { path: cap.path, action: 'translate' })}}"><i class="icon-globe"></i> View Translated</button>
    </form>

    <div class="btn-toolbar">
      
      {{#approveHref}}
      <div class="btn-group">
        <a href="{{approveHref}}" class="btn btn-success ctrl-approve"><i class="icon-ok"></i> Approve</a>
      </div>
      {{/approveHref}}

      <div class="btn-group">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
      </div>
    </div>
  </div>
</div>