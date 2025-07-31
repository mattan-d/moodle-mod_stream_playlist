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
 * Main.
 *
 * @package
 * @category    admin
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
const define = window.define // Declare the define variable before using it
define(["jquery", "jqueryui", "core/ajax", "core/notification", "core/str", "core/url"], (
  $,
  jqui,
  ajax,
  notification,
  str,
  url,
) => ({
  init: function () {
    var self = this

    this.elements = $("#stream-elements")
    this.loadingbars = url.imageUrl("icones/loading-bars", "stream")
    this.selectedIds = ($("input[name=identifier]").val() || "").split(",").filter(Boolean)
    this.videoOrder = []
    this.collectionMode = $("#id_collection_mode").is(":checked")

    // Clean up selectedIds if it contains collection mode markers
    this.selectedIds = this.selectedIds.filter(
      (id) => id !== "auto_collection" && id !== "auto_collection_pending" && id.trim() !== "",
    )

    // Initialize video order from existing data
    try {
      var orderData = $("input[name=video_order]").val()
      if (orderData) {
        this.videoOrder = JSON.parse(orderData)
      }
    } catch (e) {
      this.videoOrder = []
    }

    // Initialize sortable playlist
    this.initSortablePlaylist()

    $("body").on("click", "#stream-elements .list-item-grid", function () {
      var itemid = $(this).data("itemid").toString()
      var index = self.selectedIds.indexOf(itemid)

      if (index > -1) {
        self.selectedIds.splice(index, 1)
        $(this).find(".item").removeClass("selected")
        // Remove from video order
        var orderIndex = self.videoOrder.indexOf(itemid)
        if (orderIndex > -1) {
          self.videoOrder.splice(orderIndex, 1)
        }
      } else {
        self.selectedIds.push(itemid)
        $(this).find(".item").addClass("selected")
        // Add to video order if not already there
        if (self.videoOrder.indexOf(itemid) === -1) {
          self.videoOrder.push(itemid)
        }
      }

      // Update form fields
      self.updateFormFields()
      self.updatePlaylistOrder()
    })

    $("body").on("click", "#stream-load #stream-sort .btn", function (e) {
      e.preventDefault()
      $("#stream-load #stream-sort .btn").removeClass("active")
      $(this).toggleClass("active")
      self.load()
    })

    $("body").on("click", ".btn-upload", (e) => {
      e.preventDefault()
      $("#upload_stream").toggle()
    })

    $("#stream-title-search").keyup(() => {
      self.load()
    })

    // Listen for collection mode changes
    $("#id_collection_mode").on("change", function () {
      self.collectionMode = $(this).is(":checked")
      self.updateFormFields()
    })

    this.load()

    window.addEventListener(
      "message",
      (event) => {
        if (event.data.iframeHeight) {
          var iframe = document.getElementById("upload_stream")
          if (iframe) {
            iframe.style.height = event.data.iframeHeight + "px"
          }
        }
      },
      false,
    )

    // Add event listener to receive messages from iframes
    window.addEventListener(
      "message",
      function (event) {
        this.message(event, self)
      }.bind(this),
      false,
    )
  },

  updateFormFields: function () {
    var identifierValue = ""

    if (this.collectionMode) {
      // In collection mode, combine auto_collection marker with selected IDs
      if (this.selectedIds.length > 0) {
        identifierValue = this.selectedIds.join(",")
      } else {
        identifierValue = "auto_collection_pending"
      }
    } else {
      // Regular mode, just use selected IDs
      identifierValue = this.selectedIds.join(",")
    }

    $("input[name=identifier]").val(identifierValue)
    $("input[name=video_order]").val(JSON.stringify(this.videoOrder))
  },

  initSortablePlaylist: function () {
    // Create playlist container if it doesn't exist
    if ($("#playlist-container").length === 0) {
      // Get localized strings first
      str
        .get_strings([
          { key: "selectedvideos", component: "mod_stream" },
          { key: "dragtoorder", component: "mod_stream" },
        ])
        .then((strings) => {
          var containerHtml =
            '<div id="playlist-container">' +
            "<h4>" +
            strings[0] +
            " (" +
            strings[1] +
            "):</h4>" +
            '<ul id="sortable-playlist" class="list-group"></ul>' +
            "</div>"

          $("#stream-load").after(containerHtml)

          // Initialize sortable after container is created
          this.initializeSortable()
          this.updatePlaylistOrder()
        })
        .catch((error) => {
          // Fallback to English if string loading fails
          var containerHtml =
            '<div id="playlist-container">' +
            "<h4>Selected Videos (Drag to reorder):</h4>" +
            '<ul id="sortable-playlist" class="list-group"></ul>' +
            "</div>"

          $("#stream-load").after(containerHtml)

          // Initialize sortable after container is created
          this.initializeSortable()
          this.updatePlaylistOrder()
        })
    } else {
      // Container already exists, just initialize sortable
      this.initializeSortable()
      this.updatePlaylistOrder()
    }
  },

  initializeSortable: function () {
    // Initialize sortable with a delay to ensure DOM is ready
    setTimeout(() => {
      if ($("#sortable-playlist").length > 0) {
        try {
          $("#sortable-playlist").sortable({
            update: (event, ui) => {
              this.updateVideoOrderFromPlaylist()
            },
            placeholder: "ui-state-highlight list-group-item",
            cursor: "move",
            tolerance: "pointer",
            opacity: 0.8,
          })
        } catch (e) {
          console.warn("jQuery UI sortable not available, using fallback drag implementation")
          this.initFallbackDragDrop()
        }
      }
    }, 100)
  },

  initFallbackDragDrop: function () {
    var self = this
    var draggedElement = null

    // Add drag and drop event listeners as fallback
    $(document).on("dragstart", ".playlist-item", function (e) {
      draggedElement = this
      $(this).addClass("dragging")
      e.originalEvent.dataTransfer.effectAllowed = "move"
      e.originalEvent.dataTransfer.setData("text/html", this.outerHTML)
    })

    $(document).on("dragend", ".playlist-item", function (e) {
      $(this).removeClass("dragging")
      draggedElement = null
    })

    $(document).on("dragover", ".playlist-item", function (e) {
      e.preventDefault()
      e.originalEvent.dataTransfer.dropEffect = "move"

      if (draggedElement !== this) {
        var rect = this.getBoundingClientRect()
        var midpoint = rect.top + rect.height / 2

        if (e.originalEvent.clientY < midpoint) {
          $(this).before(draggedElement)
        } else {
          $(this).after(draggedElement)
        }

        self.updateVideoOrderFromPlaylist()
      }
    })

    // Make items draggable
    $(document).on("mouseenter", ".playlist-item", function () {
      $(this).attr("draggable", "true")
    })
  },

  updatePlaylistOrder: function () {
    var playlist = $("#sortable-playlist")
    playlist.empty()

    // Add selected videos to playlist in order
    this.videoOrder.forEach((videoId) => {
      if (this.selectedIds.indexOf(videoId) > -1) {
        var videoElement = $("#video_identifier_" + videoId)
        if (videoElement.length > 0) {
          var title = videoElement.find(".title").text()
          var thumbnail = videoElement.find("img").attr("src")

          var playlistItem = $(
            '<li class="list-group-item playlist-item" data-video-id="' +
              videoId +
              '" draggable="true">' +
              '<div class="d-flex align-items-center">' +
              '<img src="' +
              thumbnail +
              '" class="playlist-thumbnail me-3" style="width: 60px; height: 34px; object-fit: cover;">' +
              '<span class="playlist-title flex-grow-1">' +
              title +
              "</span>" +
              '<span class="drag-handle ms-2" style="cursor: move;">⋮⋮</span>' +
              "</div>" +
              "</li>",
          )

          playlist.append(playlistItem)
        }
      }
    })

    // Show/hide playlist based on selection
    if (this.selectedIds.length > 0) {
      $("#playlist-container").show()
    } else {
      $("#playlist-container").hide()
    }
  },

  updateVideoOrderFromPlaylist: function () {
    var newOrder = []
    $("#sortable-playlist .playlist-item").each(function () {
      newOrder.push($(this).data("video-id").toString())
    })
    this.videoOrder = newOrder
    this.updateFormFields()
  },

  message: (event, self) => {
    // Check if the message contains the streamid
    if (event.data && event.data.streamid) {
      var streamid = event.data.streamid.toString()
      if (self.selectedIds.indexOf(streamid) === -1) {
        self.selectedIds.push(streamid)
        if (self.videoOrder.indexOf(streamid) === -1) {
          self.videoOrder.push(streamid)
        }
      }
      self.updateFormFields()
      $("#upload_stream").hide()
      self.load()
    }
  },

  load: function () {
    var sort = $("#stream-load #stream-sort .btn.active").attr("data-name")

    this.elements.html('<div style="text-align:center"><img height="80" src="' + this.loadingbars + '" ></div>')

    ajax
      .call([
        {
          methodname: "mod_stream_video_list",
          args: {
            term: $("#stream-title-search").val(),
            courseid: $('input[name="course"]').val(),
            sort: sort,
          },
        },
      ])[0]
      .then((response) => this.list(response, this))
      .catch((error) => this.failed(error, this))
  },

  failed: (error, self) =>
    str
      .get_string("servererror", "moodle")
      .then((connectionfailed) => self.elements.html('<div class="alert alert-danger">' + connectionfailed + "</div>")),

  list: (response, self) => {
    if (response.status == "success") {
      if (response.videos.length) {
        self.elements.html("")

        $.each(response.videos, (key, video) => {
          str
            .get_strings([
              { key: "views", component: "mod_stream" },
              { key: "before", component: "mod_stream" },
            ])
            .then((string) => {
              var html =
                '<div class="col list-item-grid" data-itemid="' +
                video.id +
                '" id="video_identifier_' +
                video.id +
                '">' +
                '<span class="item"><div class="thumbnail">' +
                '<img src="' +
                video.thumbnail +
                '" class="img-fluid img-rounded">' +
                '<span class="datecreated">' +
                video.datecreated +
                '</span><span class="duration">' +
                video.duration +
                '</span></div><span class="title">' +
                video.title +
                '</span><span class="details">' +
                video.views +
                " " +
                string[0] +
                ' <span class="bubble">●</span>' +
                " " +
                string[1] +
                " " +
                video.elapsed +
                "</span></span></div>"
              self.elements.append(html)

              if (self.selectedIds.indexOf(video.id.toString()) > -1) {
                $("#video_identifier_" + video.id)
                  .find(".item")
                  .addClass("selected")
              }

              // Update playlist after adding videos
              self.updatePlaylistOrder()

              return null
            })
            .catch((error) => self.failed(error, self))
        })
      } else {
        return str
          .get_string("noresults", "mod_stream")
          .then((noresults) => self.elements.html('<div class="alert alert-info">' + noresults + "</div>"))
      }
    }
    return true
  },
}))
