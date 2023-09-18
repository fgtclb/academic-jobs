(function() {

  const editorConfig = {
    language: 'de',
    height: 200,
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

  let waitCKEDITOR = setInterval(function() {
    console.log('Wait for ckeditor');
    if (window.CKEDITOR) {
      clearInterval(waitCKEDITOR);
      CKEDITOR.replace('job-description', editorConfig);
    }
  }, 100);
})();
