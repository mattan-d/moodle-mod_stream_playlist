define(["jquery", "core/ajax"], ($, ajax) => ({
  init: () => {
    $(".playlist-item").on("click", function () {
      var playlistItem = $(this)
      var container = playlistItem.closest(".stream-playlist-container")
      var mainVideoContainer = container.find(".stream-main-video")

      if (playlistItem.hasClass("active")) {
        return // Don't reload if it's already active.
      }

      var identifier = playlistItem.data("identifier")
      var cmid = container.data("cmid")
      var includeaudio = container.data("includeaudio")

      // Set active state
      container.find(".playlist-item").removeClass("active")
      playlistItem.addClass("active")

      // Show loading indicator
      mainVideoContainer.html(
        '<div class="loading-overlay"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>',
      )

      ajax
        .call([
          {
            methodname: "mod_stream_get_player",
            args: {
              cmid: cmid,
              identifier: identifier,
              includeaudio: includeaudio,
            },
          },
        ])[0]
        .done((response) => {
          mainVideoContainer.html(response.html)
        })
        .fail((ex) => {
          // Handle error
          mainVideoContainer.html('<div class="alert alert-danger">Failed to load video.</div>')
          console.error(ex)
        })
    })
  },
}))
