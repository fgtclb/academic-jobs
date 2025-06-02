(function() {

  const editorConfig = {
    language: 'de',
    height: 200,
    versionCheck: false,
    format_tags: 'p',
    toolbarGroups: [
      { name: 'basicstyles', groups: [ 'basicstyles' ] },
      { name: 'paragraph', groups: [ 'list' ] },
      { name: 'clipboard', groups: [ 'cleanup' ] }
    ],
    customConfig: '',
    removeButtons: [
        'Strike',
        'Subscript',
        'Superscript'
    ]
  };

  const rteFields = document.querySelectorAll('.rte');

  if (!rteFields) {
    return;
  }

  let waitCKEDITOR = setInterval(function() {
    console.log('Wait for ckeditor');
    if (window.CKEDITOR) {
      clearInterval(waitCKEDITOR);

      rteFields.forEach(function(field) {
        CKEDITOR.replace(field.id, editorConfig);
      });
    }
  }, 100);
})();
