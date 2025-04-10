(function ($) {
  "use strict";

  $(function () {
    $("#tabs").tabs();
  });

  $(window).scroll(function () {
    var scroll = $(window).scrollTop();
    var box = $(".header-text").height() || 0;
    var header = $("header").height() || 0;

    if (scroll >= box - header) {
      $("header").addClass("background-header");
    } else {
      $("header").removeClass("background-header");
    }
  });

  $(".schedule-filter li").on("click", function () {
    var tsfilter = $(this).data("tsfilter");
    $(".schedule-filter li").removeClass("active");
    $(this).addClass("active");
    if (tsfilter == "all") {
      $(".schedule-table").removeClass("filtering");
      $(".ts-item").removeClass("show");
    } else {
      $(".schedule-table").addClass("filtering");
    }
    $(".ts-item").each(function () {
      $(this).removeClass("show");
      if ($(this).data("tsmeta") == tsfilter) {
        $(this).addClass("show");
      }
    });
  });

  // Window Resize Mobile Menu Fix
  mobileNav();

  // Scroll animation init
  window.sr = new scrollReveal();

  // Menu Dropdown Toggle
  if ($(".menu-trigger").length) {
    $(".menu-trigger").on("click", function () {
      $(this).toggleClass("active");
      $(".header-area .nav").slideToggle(200);
    });
  }

  $(document).ready(function () {
    // Cache the navigation links
    var $navLinks = $('nav a[href^="#"]');

    // Smooth scroll function
    function smoothScroll(target) {
      if ($(target).length) {
        $("html, body").animate(
          {
            scrollTop: $(target).offset().top,
          },
          800
        );
        return false;
      }
    }

    // Handle scroll events
    function onScroll() {
      var scrollPos = $(document).scrollTop();

      // Check each navigation link
      $navLinks.each(function () {
        var currLink = $(this);
        var refElement = $(currLink.attr("href"));

        // Make sure the element exists before trying to access its properties
        if (refElement.length) {
          var offset = refElement.offset();
          if (
            offset &&
            offset.top <= scrollPos + 100 &&
            offset.top + refElement.height() > scrollPos
          ) {
            $navLinks.removeClass("active");
            currLink.addClass("active");
          } else {
            currLink.removeClass("active");
          }
        }
      });
    }

    // Bind scroll event
    $(document).on("scroll", onScroll);

    // Handle click events on navigation links
    $navLinks.on("click", function (e) {
      e.preventDefault();
      $(document).off("scroll", onScroll);

      $navLinks.removeClass("active");
      $(this).addClass("active");

      var target = this.hash;
      smoothScroll(target);

      // Re-enable scroll handling after animation
      setTimeout(function () {
        $(document).on("scroll", onScroll);
      }, 850);
    });
  });

  // Add scroll position debugging
  $(window).on("scroll", function () {
    console.log("Scroll position:", $(document).scrollTop());
  });

  // Add element position debugging
  function logElementPositions() {
    $(".nav a").each(function () {
      let href = $(this).attr("href");
      if (href && href !== "#") {
        let element = $(href);
        if (element.length) {
          console.log("Element position:", href, element.position());
        }
      }
    });
  }

  // Call on page load
  $(document).ready(function () {
    setTimeout(logElementPositions, 1000);
  });

  // Add these utility functions
  function isValidSelector(selector) {
    try {
      $(selector);
      return true;
    } catch (error) {
      return false;
    }
  }

  // Initialize Bootstrap components
  $(document).ready(function () {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Initialize popovers
    $('[data-toggle="popover"]').popover();

    // Initialize modals
    $(".modal").modal({
      show: false,
    });

    // Debug log
    console.log("Custom.js initialized");
  });

  // Handle modal events
  $(document).ready(function () {
    $("#editProfileModal")
      .on("show.bs.modal", function () {
        console.log("Modal opening");
      })
      .on("shown.bs.modal", function () {
        console.log("Modal opened");
      })
      .on("hide.bs.modal", function () {
        console.log("Modal closing");
      })
      .on("hidden.bs.modal", function () {
        console.log("Modal closed");
      });
  });

  // Error handling for scroll events
  window.onerror = function (msg, url, lineNo, columnNo, error) {
    console.log(
      "Error: " +
        msg +
        "\nURL: " +
        url +
        "\nLine: " +
        lineNo +
        "\nColumn: " +
        columnNo +
        "\nError object: " +
        JSON.stringify(error)
    );
    return false;
  };

  // Page loading animation
  $(window).on("load", function () {
    $("#js-preloader").addClass("loaded");
  });

  // Window Resize Mobile Menu Fix
  $(window).on("resize", function () {
    mobileNav();
  });

  // Window Resize Mobile Menu Fix
  function mobileNav() {
    var width = $(window).width();
    $(".submenu").on("click", function () {
      if (width < 767) {
        $(".submenu ul").removeClass("active");
        $(this).find("ul").toggleClass("active");
      }
    });
  }

  // Initialize mobile nav
  $(window).on("resize", mobileNav);
  mobileNav();

  // Profile update handling
  $(document).ready(function () {
    const form = $("#profileForm");
    const saveBtn = $("#saveProfileBtn");

    if (form.length && saveBtn.length) {
      saveBtn.on("click", function (e) {
        e.preventDefault();

        // Disable button and show loading state
        saveBtn
          .prop("disabled", true)
          .html(
            '<span class="spinner-border spinner-border-sm"></span> Saving...'
          );

        const formData = new FormData(form[0]);

        $.ajax({
          url: "update_profile.php",
          type: "POST",
          data: formData,
          processData: false,
          contentType: false,
          success: function (response) {
            if (response.success) {
              // Update display values
              $("#display-phone").text(formData.get("mobile_no"));
              $("#display-address").text(formData.get("address"));

              // Show success message and close modal
              alert("Profile updated successfully!");
              $("#editProfileModal").modal("hide");

              // Optional: Reload page after short delay
              setTimeout(() => {
                location.reload();
              }, 500);
            } else {
              alert(response.message || "Update failed");
            }
          },
          error: function () {
            alert("Error updating profile");
          },
          complete: function () {
            saveBtn.prop("disabled", false).html("Save Changes");
          },
        });
      });
    }
  });
})(window.jQuery);
