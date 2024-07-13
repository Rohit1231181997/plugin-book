jQuery(document).ready(function ($) {
  // Date Form Validation
  $("#dateForm").on("submit", function (e) {
    alert('hyyyyy')
      var startDate = $("#start_date").val();
      var endDate = $("#end_date").val();
      var errorMessage = "";
      consol.log(startDate)

      if (!startDate || !endDate) {
          errorMessage = "Both dates are required.";
      } else if (startDate > endDate) {
          errorMessage = "Start date cannot be greater than the end date.";
      }

      if (errorMessage) {
          $("#error").text(errorMessage).show();
          e.preventDefault();
      } else {
          $("#error").hide();
      }
  });

  // Contact Form AJAX Submission
  $('form#contact-form').on('submit', function (e) {
      e.preventDefault();

      var formData = $(this).serialize();

      $.ajax({
          type: 'POST',
          url: ajax_object.ajax_url,
          data: formData + '&action=handle_cf7_submission',
          success: function (response) {
              if (response.success) {
                 
              } else {
                  
              }
          },
          error: function (xhr, status, error) {
              console.log(xhr.responseText);
          }
      });
  });

  // CF7 Form Validation
  $("form.wpcf7-form").each(function () {
      var $form = $(this);
      $form.validate({
          errorClass: "error",
          rules: {
              'name': {
                  required: true,
                  no_url: true,
                  lettersonly: true,
                  noSpace: true,
              },
              'mobile': {
                  required: true,
                  no_url: true,
                  noSpace: true,
                  minlength: 6,
                  maxlength: 14
              },
              'industry': {
                  required: true,
                  Default: true,
              },
              'industry_type': {
                  required: true,
                  notDefault: true,
              },
              'email': {
                  required: true,
                  no_url: true,
                  noSpace: true,
              },
          },
          messages: {
              'name': {
                  required: "Enter a Name",
                  no_url: "URLs are not allowed in this field.",
                  lettersonly: "Enter a valid name.",
                  noSpace: "No space please and don't leave it empty",
              },
              'industry': {
                  required: "Select Demo type",
                  Default: "Select Demo type",
              },
              'industry_type': {
                  required: "Select industry type",
                  notDefault: "Select industry type",
              },
              'mobile': {
                  required: "Enter Phone Number",
                  minlength: "Minimum six digits",
                  maxlength: "Maximum fourteen digits",
                  no_url: "URLs are not allowed in this field.",
                  lettersonly: "Enter a valid Phone Number.",
                  noSpace: "No space please and don't leave it empty",
              },
              'email': {
                  required: "Enter an Email",
                  no_url: "URLs are not allowed in this field.",
                  noSpace: "No space please and don't leave it empty",
              },
          },
      });

      // Custom Validation Methods
      var customMethods = {
          no_url: [
              function (value, element) {
                  var re = /^[a-zA-Z0-9\-\.\:\\]+\.(com|org|net|mil|edu|COM|ORG|NET|MIL|EDU)$/;
                  var re1 = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
                  var trimmed = $.trim(value);
                  if (trimmed === "") {
                      return true;
                  }
                  if (trimmed.match(re) === null && !re1.test(trimmed)) {
                      return true;
                  }
                  return false; // Return false if URL is found
              },
              "URLs are not allowed in this field.",
          ],
          notDefault: [
              function (value, element) {
                  return value !== "--Select industry type--";
              },
              "Select industry type",
          ],
          Default: [
              function (value, element) {
                  return value !== "--Select Demo type--";
              },
              "Select Demo type",
          ],
          lettersonly: [
              function (value, element) {
                  return this.optional(element) || /^[a-zA-Z\s]+$/.test(value);
              },
              "Enter a valid name.",
          ],
          noSpace: [
              function (value, element) {
                  return value === "" || value.trim().length !== 0;
              },
              "No space please and don't leave it empty",
          ],
          HtmlTag: [
              function (value, element) {
                  return this.optional(element) || !/<[^>]*>/g.test(value);
              },
              "HTML tags are not allowed.",
          ],
      };

      // Register custom validation methods
      $.each(customMethods, function (name, params) {
          $.validator.addMethod(name, params[0], params[1]);
      });

      formPrevent($form);
  });

  function formPrevent($form) {
      var submitbtn = $form.find(".wpcf7-submit");
      submitbtn.on("click", function (event) {
          var errors = $form.find(":input.error").length;

          if (errors > 0) {
              event.preventDefault();
              $form.addClass("invalid");
              $form.find(".wpcf7-response-output").show().text("One or more fields have an error. Please check and try again.");
              return false;
          } else {
              $form.find(".wpcf7-response-output").hide();
          }
      });

      document.addEventListener(
          "wpcf7mailsent",
          function (event) {
              var form = event.target;
              if ($(form).is($form)) {
                  $form.removeClass("invalid");
                  $form.find(".wpcf7-response-output").show().text("Thank you for your message. It has been sent.");
              }
          },
          false
      );
  }
});

document.addEventListener('DOMContentLoaded', function() {
  const formDiv = document.querySelector("#form");
  const modalSubscribe = document.querySelector("#modal-subscribe");

  if (formDiv) {
      document.addEventListener('wpcf7mailsent', function(event) {
          event.preventDefault();
          const contactformd = formDiv.querySelector("form");
          const myname = contactformd.querySelector("#myname").value;
          const myemail = contactformd.querySelector("#myemail").value;  
          const mynumber= contactformd.querySelector("#mynumber").value;
         
          const calendlyLink = 'https://calendly.com/textdrip/demo?name='+myname+'&email='+myemail+'&a1='+mynumber;
       
          Calendly.initPopupWidget({url:calendlyLink});
          if (modalSubscribe) {
              modalSubscribe.style.display = 'none';
          }
          return false;
      });
  }

});