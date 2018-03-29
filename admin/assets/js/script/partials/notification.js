

/*
 * Display a warning when the page opened using "file" protocol
 */
if ( location.protocol == 'file:' ) {
  app.toast('Please open the page using "http" protocol for full functionality.', {
    duration: 15000,
    actionTitle: 'Read more',
    actionUrl: ''
  })
}

