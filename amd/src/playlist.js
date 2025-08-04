// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Playlist functionality for stream videos.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var define = window.define // Declare the define variable

define(["jquery", "core/ajax", "core/notification"], ($, ajax, notification) => {
  var PlaylistManager = {
    cmid: null,
    currentVideoId: null,
    videos: [],
    autoPlayPlaylist: false,
    includeAudio: false,

    init: function () {
      var container = $(".stream-playlist-container")
      if (container.length === 0) {
        return
      }

      this.cmid = container.data("cmid")
      this.includeAudio = container.data("includeaudio") || false
      this.autoPlayPlaylist = container.data("autoplayplaylist") || false

      // Get video data from playlist items
      this.videos = []
      $(".playlist-item").each(function () {
        var $item = $(this)
        PlaylistManager.videos.push({
          id: $item.data("identifier"),
          element: $item,
        })
      })

      // Set current video (first active or first video)
      var activeVideo = $(".playlist-item.active")
      if (activeVideo.length > 0) {
        this.currentVideoId = activeVideo.data("identifier")
      } else if (this.videos.length > 0) {
        this.currentVideoId = this.videos[0].id
        this.videos[0].element.addClass("active")
      }

      this.bindEvents()
      this.setupVideoEndListener()
    },

    bindEvents: function () {
      var self = this

      // Handle playlist item clicks
      $(document).on("click", ".playlist-item", function (e) {
        e.preventDefault()
        var videoId = $(this).data("identifier")
        self.playVideo(videoId)
      })
    },

    setupVideoEndListener: function () {
      

      // Listen for video end events from the player iframe
      window.addEventListener(
        "message",
        (event) => {
          if (event.data && event.data.type === "videoEnded") {
            if (this.autoPlayPlaylist) {
              this.playNextVideo()
            }
          }
        },
        false,
      )

      // Fallback: Monitor video elements directly if available
      $(document).on("ended", "video", () => {
        if (this.autoPlayPlaylist) {
          this.playNextVideo()
        }
      })
    },

    playVideo: function (videoId) {
      

      if (!videoId || videoId === this.currentVideoId) {
        return
      }

      // Update UI
      $(".playlist-item").removeClass("active")
      $('.playlist-item[data-identifier="' + videoId + '"]').addClass("active")

      // Load new video player
      this.loadVideoPlayer(videoId)
        .then((playerHtml) => {
          $(".stream-main-video").html(playerHtml)
          this.currentVideoId = videoId
          this.markVideoAsViewed(videoId)
        })
        .catch((error) => {
          notification.exception(error)
        })
    },

    playNextVideo: function () {
      var currentIndex = -1

      // Find current video index
      for (var i = 0; i < this.videos.length; i++) {
        if (this.videos[i].id === this.currentVideoId) {
          currentIndex = i
          break
        }
      }

      // Play next video if available
      if (currentIndex >= 0 && currentIndex < this.videos.length - 1) {
        var nextVideo = this.videos[currentIndex + 1]
        this.playVideo(nextVideo.id)
      }
    },

    playPreviousVideo: function () {
      var currentIndex = -1

      // Find current video index
      for (var i = 0; i < this.videos.length; i++) {
        if (this.videos[i].id === this.currentVideoId) {
          currentIndex = i
          break
        }
      }

      // Play previous video if available
      if (currentIndex > 0) {
        var previousVideo = this.videos[currentIndex - 1]
        this.playVideo(previousVideo.id)
      }
    },

    loadVideoPlayer: (videoId) =>
      new Promise((resolve, reject) => {
        ajax
          .call([
            {
              methodname: "mod_stream_get_player",
              args: {
                cmid: PlaylistManager.cmid,
                identifier: videoId,
                includeaudio: PlaylistManager.includeAudio,
              },
            },
          ])[0]
          .then((response) => {
            if (response.status === "success") {
              resolve(response.player)
            } else {
              reject(new Error(response.message || "Failed to load video player"))
            }
          })
          .catch((error) => {
            reject(error)
          })
      }),

    markVideoAsViewed: function (videoId) {
      // Mark video as viewed in the playlist
      var $item = $('.playlist-item[data-identifier="' + videoId + '"]')
      if ($item.find(".playlist-viewed-badge").length === 0) {
        $item
          .find(".playlist-item-details")
          .append('<span class="badge badge-success playlist-viewed-badge">Viewed</span>')
      }

      // Send viewed status to server
      ajax
        .call([
          {
            methodname: "mod_stream_mark_viewed",
            args: {
              cmid: this.cmid,
              videoid: videoId,
            },
          },
        ])[0]
        .catch((error) => {
          // Silently handle errors for view tracking
          console.warn("Failed to mark video as viewed:", error)
        })
    },
  }

  return {
    init: () => {
      $(document).ready(() => {
        PlaylistManager.init()
      })
    },
  }
})
