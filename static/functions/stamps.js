jQuery(function ($) {

  // Load all the images AFTER the site CSS / background images have loaded
  // (Adding 5ms delay between images to avoid weird error with loading 600+ images)
  // Suppose we could actually wait until each is loaded... but leaving that for another day
  $(window).load(function() {

    var needSrc = jQuery.makeArray($('[data-src]'));
    var intervalId = window.setInterval(loadSrc, 5);
    function loadSrc() {
      if (needSrc.length > 0) {
        $(needSrc.shift()).attr('src', function() { return $(this).data('src'); });
      } else {
        clearInterval(intervalId);
      }
    }
  });

  // Verify and prep form data for stamp purchase
  $('#checkout').click(function() {
    var cart = calcCart();
    if (cart.count === 0) {
      alert('Nothing to buy!');
      return false;
    }
    if (cart.amount > Number($('#bonusCredits').text())) {
      alert('Not enough cubits!');
      return false;
    }
    $('#stamps').val(cart.stampIds);
    return confirm('Purchase ' + cart.count + (cart.count>1 ? ' stamps for ' : ' stamp for ') + cart.amount.toLocaleString() + ' cubits?');

  });

  function calcCart() {
    var count = 0;
    var amount = 0;
    var stampIds = [];
    $('#cartStamps').children().each(function () {
      count++;
      amount += $(this).data('cost');
      stampIds.push($(this).data('id'));
    });
    var itemLabel = count === 1 ? 'Item' : 'Items';
    $('#cartResults')
      .html(count + ' ' + itemLabel + '<br/>' + amount.toLocaleString() + ' Cubits')
      .attr('class', amount > Number($('#bonusCredits').text()) ? 'error' : '');

    $('#cartCheckout').toggle(count > 0);
    return {count, amount, stampIds};
  }

  function removeFromCart() {
    var stampId = $(this).data('stamp-id');
    $('#' + stampId).on('click', addToCart).removeClass('inCart').addClass('buyStamp');
    $(this).remove();
    calcCart();
  }

  function addToCart(e) {
    $(this).clone()
      .removeAttr('id')
      .data('stamp-id', $(this).attr('id'))
      .on('click', removeFromCart)
      .appendTo('#cartStamps');
    $(this).off('click').removeClass('buyStamp').addClass('inCart');
    calcCart();
  }

  $('.buyStamp').click(addToCart);

  $('.TOC').click(function (e) {
    $('#stampFilter').val('');
    $('#marketScroll > *').show();
    $($(this).data('target')).scrollintoview($('#marketScroll'));
  });

  // Run the stamp search
  $('#stampFilter').on('input', function(e) {
    if ($(this).val() == '') {
      $('#marketScroll > *').show();
    } else {
      var searchStr = new RegExp($(this).val(), 'ig');
      $('#marketScroll > *').hide();
      $('#marketScroll > img')
        .filter(function() {
          return $(this).data('title').toString().match(searchStr);
        }).show();
    }
  });

});

/*!
 * jQuery scrollintoview() plugin and :scrollable selector filter
 *
 * Version 1.8 (14 Jul 2011)
 * Requires jQuery 1.4 or newer
 *
 * Copyright (c) 2011 Robert Koritnik
 * Licensed under the terms of the MIT license
 * http://www.opensource.org/licenses/mit-license.php
 * (modified for NBL use & preferences)
 */
(function($){var borders=function(domElement,styles){styles=styles||(document.defaultView&&document.defaultView.getComputedStyle?document.defaultView.getComputedStyle(domElement,null):domElement.currentStyle);var px=!(!document.defaultView||!document.defaultView.getComputedStyle),b={top:parseFloat(px?styles.borderTopWidth:$.css(domElement,"borderTopWidth"))||0,left:parseFloat(px?styles.borderLeftWidth:$.css(domElement,"borderLeftWidth"))||0,bottom:parseFloat(px?styles.borderBottomWidth:$.css(domElement,"borderBottomWidth"))||0,right:parseFloat(px?styles.borderRightWidth:$.css(domElement,"borderRightWidth"))||0};return{top:b.top,left:b.left,bottom:b.bottom,right:b.right,vertical:b.top+b.bottom,horizontal:b.left+b.right}},dimensions=function($element){return{border:borders($element[0]),scroll:{top:$element.scrollTop(),left:$element.scrollLeft()},scrollbar:{right:$element.innerWidth()-$element[0].clientWidth,bottom:$element.innerHeight()-$element[0].clientHeight},rect:(r=$element[0].getBoundingClientRect(),{top:r.top,left:r.left,bottom:r.bottom,right:r.right})};var r};$.fn.extend({scrollintoview:function(scroller){var el=this.eq(0);if(scroller.length>0){scroller=scroller.eq(0);var dim={e:dimensions(el),s:dimensions(scroller)},rel={top:dim.e.rect.top-(dim.s.rect.top+dim.s.border.top),bottom:dim.s.rect.bottom-dim.s.border.bottom-dim.s.scrollbar.bottom-dim.e.rect.bottom,left:dim.e.rect.left-(dim.s.rect.left+dim.s.border.left),right:dim.s.rect.right-dim.s.border.right-dim.s.scrollbar.right-dim.e.rect.right},animOptions={};animOptions.scrollTop=dim.s.scroll.top+rel.top,$.isEmptyObject(animOptions)||scroller.animate(animOptions,"fast").eq(0)}return this}})})(jQuery);
