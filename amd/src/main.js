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
define(["jquery", "core/ajax", "core/notification", "core/str", "core/url"], ($, ajax, notification, str, url) => ({
  init: function () {
    var self = this

    this.elements = $("#stream-elements")
    this.loadingbars = url.imageUrl("icones/loading-bars", "stream")
    this.selectedIds = ($("#id_identifier").val() || "").split(",").filter(Boolean)

    $("body").on("click", "#stream-elements .list-item-grid", function () {
      var itemid = $(this).data("itemid").toString()
      var index = self.selectedIds.indexOf(itemid)

      if (index > -1) {
        self.selectedIds.splice(index, 1)
        $(this).find(".item").removeClass("selected")
      } else {
        self.selectedIds.push(itemid)
        $(this).find(".item").addClass("selected")
      }
      $("#id_identifier").val(self.selectedIds.join(","))
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
  message: (event, self) => {
    // Check if the message contains the streamid
    if (event.data && event.data.streamid) {
      var streamid = event.data.streamid.toString()
      if (self.selectedIds.indexOf(streamid) === -1) {
        self.selectedIds.push(streamid)
      }
      $("#id_identifier").val(self.selectedIds.join(","))
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
