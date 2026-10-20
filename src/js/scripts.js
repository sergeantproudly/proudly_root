var __widthMobile = 1000;
var __widthMobileTablet = 1000;
var __widthMobileTabletMiddle = 850;
var __widthMobileSmall = 540;
var __isMobile = ($(window).width() <= __widthMobile);
var __isMobileTablet = ($(window).width() <= __widthMobileTablet);
var __isMobileTabletMiddle = ($(window).width() <= __widthMobileTabletMiddle);
var __isMobileSmall = ($(window).width() <= __widthMobileSmall);
var __animationSpeed = 800;

function initElements(element) {
	$element=$(element ? element : 'body');

	$(window).on('resize',function(){
		onResize();
	});

	$.widget('app.selectmenu', $.ui.selectmenu, {
		_drawButton: function() {
		    this._super();
		    var selected = this.element
		    .find('[selected]')
		    .length,
		        placeholder = this.options.placeholder;

		    if (!selected && placeholder) {
		      	this.buttonItem.text(placeholder).addClass('placeholder');
		    } else {
		    	this.buttonItem.removeClass('placeholder');
		    }
		}
	});

	$.datepicker.regional['ru']={
           closeText: 'Закрыть',
           prevText: '&#x3c;Пред',
           nextText: 'След&#x3e;',
           currentText: 'Сегодня',
           monthNames: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
           monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
           dayNames: ['воскресенье', 'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота'],
           dayNamesShort: ['вск', 'пнд', 'втр', 'срд', 'чтв', 'птн', 'сбт'],
           dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
           weekHeader: 'Нед',
           dateFormat: 'dd.mm.yy',
           firstDay: 1,
           isRTL: false,
           showMonthAfterYear: false,
           yearSuffix: ''
    };
    $.datepicker.setDefaults($.datepicker.regional['ru']);

	$element.find('select').each(function(i, select) {
		// editable select
		if (typeof($(select).attr('editable')) != 'undefined' && $(select).attr('editable') !== 'false') {
			$(select).editableSelect({ 
				effects: 'fade',
				source: $(select).attr('source') ? $(select).attr('source') : false
			}).on('change.editable-select', function(e) {
				var $holder = $(e.target).closest('.input-holder');
				if ($holder.find('.es-input').val()) {
					$(e.target).closest('.input-holder').addClass('focused');
				} else {
					$(e.target).closest('.input-holder').removeClass('focused');
				}
			});

		// simple select
		} else {
			if ($(select).offset().left + 370 > $(window).width()) {
				$(select).attr('data-pos', 'right');
			}

			var offset = $(select).attr('data-offset');
			if ($(select).attr('data-pos') == 'right') {
				var data = {
					position: {my : "right"+(offset?"+"+offset:"")+" top-2", at: "right bottom"}
				}
			} else {
				var data = {
					position: {my : "left"+(offset?"+"+offset:"")+" top-2"}
				}
			}
			if (typeof($(select).attr('placeholder')) != 'undefined') {
				data['placeholder'] = $(select).attr('placeholder');
			}
			data['change'] = function(e, ui) {
				$(ui.item.element).closest('.input-holder').addClass('focused');
			}
			data['appendTo'] = $(select).parent();
			$(select).selectmenu(data);
			if (typeof($(select).attr('placeholder')) != 'undefined') {
				$(select).prepend('<option value="" disabled selected>' + data['placeholder'] + '</option>');
			}
		}
	});

	$element.find('.js-date').each(function(index,input){
		var datepicker_options = {
			inline: true,
			language: 'ru',
		    changeYear: true,
		    changeMonth: true,
		    showOtherMonths: true
		};
		var minYear=$(input).attr('data-min-year');
		if(minYear) datepicker_options.minDate='01.01.'+minYear;
		else minYear='c-10';
		var maxYear=$(input).attr('data-max-year');
		if(maxYear) datepicker_options.maxDate='01.01.'+maxYear;
		else maxYear='c+10';
		var defaultDate=$(input).attr('data-default-date');
		if(defaultDate) datepicker_options.defaultDate=defaultDate;
		datepicker_options.yearRange=[minYear,maxYear].join(':');
		
		$(input).attr('type','text').datepicker(datepicker_options).addClass('date').val($(input).attr('value')).after('<i></i>');
		$(input).next('i').click(function() {
			$(this).prev('input').datepicker('show');
			//initElements($('#ui-datepicker-div'));
		});
	});

	$element.find('input[type="checkbox"], input[type="radio"]').not('.ask-agree input').checkboxradio(); 
	$element.find('input[data-mask]').each(function(index,input){
		$(input).inputmask($(this).attr('data-mask'),{
			'clearMaskOnLostFocus': true
		});
	});
	$('input[type="tel"]').inputmask( { 'mask': '+7 (999) 999-99-99' } );

	$element.find('.modal-close, .js-close, .modal .js-cancel').click(function(e) {
		e.preventDefault();
		e.stopPropagation();

		if ($element.find('.modal-wrapper:visible').length > 1) {
			$element.find('.modal-wrapper[data-transparent]').stop().animate({'opacity': 1}, __animationSpeed);
			hideModal(this, true);
		} else {
			hideModal(this, false);
		}
	});

	$element.find('.tabs, .js-tabs').lightTabs();

	$element.find('.js-scroll').each(function(index, block) {
		if (!$(block).attr('data-on-demand')) {
			scrollInit(block);
		}
	});

	$('body').mouseup(function(e) {
		/*
		if ($('.modal-fadeout').css('display') == 'block' && !$('html').hasClass('html-mobile-opened')) {
			if (!$(e.target).closest('.contents').length && !$(e.target).closest('.ui-selectmenu-menu').length && !$(e.target).closest('.ui-datepicker').length) {
				hideModal();
			}
		}
		*/
		if ($('html').hasClass('html-mobile-opened')) {
			if (!$(e.target).closest('.menu-holder').length) {
				$('nav .close').click();
			}
		}

	}).keypress(function(e){
		if ($('.modal-fadeout').css('display') == 'block') {
			if (!e)e = window.event;
			var key = e.keyCode||e.which;
			if (key == 27){
				hideModal();
			} 
		}
		if ($('html').hasClass('html-mobile-opened')) {
			if (!e)e = window.event;
			var key = e.keyCode||e.which;
			if (key == 27){
				$('nav .close').click();
			}
		}
	});

	$element.find('.fld-holder input').keydown(function() {
		if ($(this).val()) {
			$(this).parent('.fld-holder').addClass('focused');
		}
	}).keyup(function() {
		if (!$(this).val()) {
			$(this).parent('.fld-holder').removeClass('focused');
		}
	}).focusout(function() {
		if (!$(this).val()) {
			$(this).parent('.fld-holder').removeClass('focused');
		}
	}).each(function(i, item) {
		if ($(item).val()) {
			$(item).parent('.fld-holder').addClass('focused');
		}
	});

	$element.find('textarea.js-autoheight').each(function(i, textarea) {
		if (!$(textarea).data('autoheight-inited')) {
			$(textarea).attr('rows', 1);
			$(textarea).on('input', function() {
				$(this).css('height', 'auto');
        		$(this).css('height', $(this)[0].scrollHeight+'px');
			});
			if ($(textarea).css('display') != 'none') $(textarea).trigger('input');
			$(textarea).data('autoheight-inited', true);
		}
	});

	fadeoutInit();
}

function onResize() {
	__isMobile = ($(window).width() <= __widthMobile);
	__isMobileTablet = ($(window).width() <= __widthMobileTablet);
	__isMobileTabletMiddle = ($(window).width() <= __widthMobileTabletMiddle);

	fadeoutInit();
}

function parseUrl(url) {
	if (typeof(url) == 'undefined') url=window.location.toString();
	var a = document.createElement('a');
	a.href = url;

	var pathname = a.pathname.match(/^\/?(\w+)/i);	

	var parser = {
		'protocol': a.protocol,
		'hostname': a.hostname,
		'port': a.port,
		'pathname': a.pathname,
		'search': a.search,
		'hash': a.hash,
		'host': a.host,
		'page': pathname?pathname[1]:''
	}		

	return parser;
} 

function redirect(url) {
  window.location = url;
}

function reload(forceGet) {
  window.location.reload(forceGet);
} 

function showModal(modal_id, dontHideOthers) {
	var $modal = $('#' + modal_id);

	if (typeof(dontHideOthers) == 'undefined' || !dontHideOthers) $('.modal-wrapper:visible').not($modal).attr('data-transparent', true).stop().animate({'opacity': 0}, __animationSpeed);

	$('.modal-fadeout').stop().fadeIn(300);
	$modal.stop().fadeIn(450).css({
		'display': $modal.hasClass('modal-ask') ? 'flex' : (__isMobileTablet ? 'block' : 'table'),
		'top': $(window).scrollTop()
	});

	var oversize = $(window).height() < $modal.find('.contents').outerHeight();

	if ($modal.attr('data-long') || oversize) {
		//$('html').addClass('html-modal-long');
	} else {
		$('html').addClass('html-modal');
	}

	$modal.find('.js-scroll').each(function(index, block) {
		scrollInit(block);
	});
}

function hideModal(sender, onlyModal) {
	var $modal = sender ? $(sender).closest('.modal-wrapper') : $('.modal-wrapper:visible');
	if (typeof(onlyModal) == 'undefined' || !onlyModal) {
		$('.modal-fadeout').stop().fadeOut(300);
		$modal.stop().fadeOut(450, function() {
			$('html').removeClass('html-modal html-modal-long');
		});
	} else {
		$modal.stop().fadeOut(450);
	}
}

function closeModal(sender) {
	if ($('.modal-wrapper:visible').length > 1) {
		$('.modal-wrapper[data-transparent]').stop().animate({'opacity': 1}, __animationSpeed);
		hideModal(sender, true);
	} else {
		hideModal(sender, false);
	}
}

function showModalConfirm(header, btn, action) {
	if (typeof(header) != 'undefined' && header) $('#modal-confirm>.modal>.contents>h1').text(header);
	if (typeof(btn) != 'undefined' && btn) $('#modal-confirm-action-btn').text(btn);
	if (typeof(action) == 'function') {
		$('#modal-confirm-action-btn').click(function(e) {
			e.preventDefault();
			e.stopPropagation();

			action();
			hideModal(this, $('.modal-wrapper:visible').length > 1);
		});
	}
	showModal('modal-confirm', true);
}

function scrollInit(block) {
	if (!$(block).data('inited')) {
		var maxHeight = $(block).attr('data-max-height');
		if (maxHeight < 0) maxHeight = $(block).parent().height() - Math.abs(maxHeight);
		if (maxHeight && $(block).outerHeight() > maxHeight) {
			$(block).css('max-height', maxHeight + 'px').jScrollPane({
					showArrows: false,
					mouseWheelSpeed: 20,
					autoReinitialise: true,
					verticalGutter: 0,
					verticalDragMinHeight: 36
				}
			);
		}
		$(block).data('inited', true);
	}
}

function fadeoutInit(node) {
	$node = $(typeof(node) == 'undefined' ? 'body' : node);
	$node.find('.js-fadeout').each(function(i, block) {
		if (!$(block).data('inited')) {
			var $holder = $('<div class="fadeout-holder"></div>').insertAfter($(block));
			$holder.html($(block));
			$(block).data('inited', true);
		}

		if (typeof($(block).attr('data-nowrap')) != 'undefined' && $(block).attr('data-nowrap') != false && $(block).attr('data-nowrap') != 'false') {
			$(block).addClass('nowrap');
		}
		$(block).scrollLeft(0);
		var w_child = 0;
		var range = document.createRange();

		$.each(block.childNodes, function(i, node) {
			if (node.nodeType != 3) {
				w_child += $(node).outerWidth(true);
			} else {
				if (typeof(range) != 'undefined') {
					range.selectNodeContents(node);
					var size = range.getClientRects();
					if (typeof(size) != 'undefined' && typeof(size[0]) != 'undefined' && typeof(size[0]['width'] != 'undefined')) w_child += size[0]['width'];
				}
			}
		});

		var maxWidth = $(block).attr('data-max-width');
		var cloneWidth = $(block).attr('data-clone-width');
		var mobileOnly = $(block).attr('data-mobile-only');

		if (!mobileOnly || (mobileOnly && __isMobileTablet)) {
			if (cloneWidth) {
				$(block).width($(cloneWidth).width());
			}
			var holderWidth = $(block).width();
			if (w_child > holderWidth && (!maxWidth || $(window).width() <= maxWidth)) {
				$(block).addClass('fadeout').removeClass('nowrap').swipe({
					swipeStatus: function(event, phase, direction, distance) {
						var offset = distance;

						if (phase === $.fn.swipe.phases.PHASE_START) {
							var origPos = $(this).scrollLeft();
							$(this).data('origPos', origPos);

						} else if (phase === $.fn.swipe.phases.PHASE_MOVE) {
							var origPos = $(this).data('origPos');

							if (direction == 'left') {
								var scroll_max = $(this).prop('scrollWidth') - $(this).width();
								var scroll_value_new = origPos - 0 + offset;
								$(this).scrollLeft(scroll_value_new);
								if (scroll_value_new >= scroll_max) $(this).addClass('scrolled-full');
								else $(this).removeClass('scrolled-full');

							} else if (direction == 'right') {
								var scroll_value_new = origPos - offset;
								$(this).scrollLeft(scroll_value_new);
								$(this).removeClass('scrolled-full');
							}

						} else if (phase === $.fn.swipe.phases.PHASE_CANCEL) {
							var origPos = $(this).data('origPos');
							$(this).scrollLeft(origPos);

						} else if (phase === $.fn.swipe.phases.PHASE_END) {
							$(this).data('origPos', $(this).scrollLeft());
						}
					},
					threshold: 70,
					preventDefaultEvents: false
				});
			} else {
				$(block).removeClass('fadeout');
			}
		}
	});
}

function editableSelectReinit(select) {
	if (typeof(select) == 'string') var $select = $('#' + select);
	else $select = $(select);

	var id = $select.attr('id');
	$('#' + id + '_es').remove();
	$select.data('editable-select', false);
	$select.editableSelect({ 
		effects: 'fade',
		source: $select.attr('source') ? $select.attr('source') : false
	}).on('change.editable-select', function(e) {
		var $holder = $(e.target).closest('.input-holder');
		if ($holder.find('.es-input').val()) {
			$(e.target).closest('.input-holder').addClass('focused');
		} else {
			$(e.target).closest('.input-holder').removeClass('focused');
		}
	});
	$('#' + id + '_input').show();
	return true;
}

function getOffsetSum(elem) {
	var t = 0, l = 0;
	while (elem) {
		t += t + parseFloat(elem.offsetTop);
		l += l + parseFloat(elem.offsetLeft);
		elem = elem.offsetParent;
	}
	return {top: Math.round(t), left: Math.round(l)};
}
function getOffsetRect(elem) {
	var box = elem.getBoundingClientRect();
	var body = document.body;
	var docElem = document.documentElement;
	var scrollTop = window.pageYOffset || docElem.scrollTop || body.scrollTop;
	var scrollLeft = window.pageXOffset || docElem.scrollLeft || body.scrollLeft;
	var clientTop = docElem.clientTop || body.clientTop || 0;
	var clientLeft = docElem.clientLeft || body.clientLeft || 0;
	var t  = box.top +  scrollTop - clientTop;
	var l = box.left + scrollLeft - clientLeft;
	return {top: Math.round(t), left: Math.round(l)};
}
function getOffset(elem) {
	if (elem.getBoundingClientRect) {
		return getOffsetRect(elem);
	} else {
		return getOffsetSum(elem);
	}
}

function scrollReset() {
	$('html, body').scrollTop(0);
}

function _scrollTo(target, offset) {
	var wh = $(window).height();
	if (typeof(offset) == 'undefined') offset =- Math.round(wh/2);
	else if (offset === false) offset = 0;
	$('html, body').animate({
		scrollTop: $(target).offset().top + offset
	}, 1000);
}

// MESSAGES FUNCTIONS
function msgSetWait(node,text,displayType){
	if(!text)text='Подождите, идет загрузка...';
	if(!displayType)displayType='block';
	$(node).before(displayType=='inline'?'<span class="wait">'+text+'</span>':'<div class="wait">'+text+'</div>');
	return $(node).prev().get(0);
}
function msgSetError(node,text,displayType){
	if(!text)return false;
	if(!displayType)displayType='block';
	$(node).before(displayType=='inline'?'<span class="error">'+text+'</span>':'<div class="error">'+text+'</div>');
	return $(node).prev().get(0);
}
function msgSetWarning(node,text,displayType){
	if(!text)return false;
	if(!displayType)displayType='block';
	$(node).before(displayType=='inline'?'<span class="warning">'+text+'</span>':'<div class="warning">'+text+'</div>');
	return $(node).prev().get(0);
}
function msgSetMessage(node,text,displayType){
	if(!text)return false;
	if(!displayType)displayType='block';
	$(node).before(displayType=='inline'?'<span class="message">'+text+'</span>':'<div class="message">'+text+'</div>');
	return $(node).prev().get(0);
}
function msgSetSuccess(node,text,displayType){
	if(!text)return false;
	if(!displayType)displayType='block';
	$(node).before(displayType=='inline'?'<span class="success">'+text+'</span>':'<div class="success">'+text+'</div>');
	return $(node).prev().get(0);
}
function msgUnset(node,animate){
	if(!animate)$(node).prev('.wait, .error, .message, .success').remove();
	else $(node).prev('.wait, .error, .message, .success').stop().slideUp(500,function(){
		$(this).remove();
	});
}

// CHECKS FUNCTIONS
function checkNotEmpty(fld,mode){

	if(typeof(mode)=='undefined')mode=1;

	if(fld.value)return true;

	else{

		if(mode==0){

			$(fld).addClass('err');

		}else if(mode==1){

			$(fld).closest('.inp-text, .inp-textarea').addClass('error');

		}	

		return false;

	}

}

function checkEmail(fld,mode){

	if(typeof(mode)=='undefined')mode=1;

	var reg=/^[a-zA-Z0-9_\.\-]+@([a-zA-Z0-9][a-zA-Z0-9\-]+\.)+[a-zA-Z]{2,6}$/;

	var str=fld.value;

	if(!str || reg.test(str))return true;

	else{

		if(mode==0){

			$(fld).addClass('err');

		}else if(mode==1){

			$(fld).closest('.inp-text, .inp-textarea').addClass('error');

		}

		return false;

	}

}

function checkPhone(fld,mode){

	if(typeof(mode)=='undefined')mode=1;

	var reg=/[0-9\+\-() ,\.]+/;

	var str=fld.value;

	if(!str || reg.test(str))return true;

	else{

		if(mode==0){

			$(fld).addClass('err');

		}else if(mode==1){

			$(fld).closest('.inp-text, .inp-textarea').addClass('error');

		}

		return false;

	}

}

function checkDate(fld,mode){

	if(typeof(mode)=='undefined')mode=1;

	var reg=/^(\d){1,2}\.(\d){1,2}\.(\d){4}$/;

	var str=fld.value;

	if(x!='')return reg.test(x);

	if(!str || reg.test(str))return true;

	else{

		if(mode==0){

			$(fld).addClass('err');

		}else if(mode==1){

			$(fld).closest('.inp-text, .inp-textarea').addClass('error');

		}

		return false;

	}

}

function checkResetStatus(elem,mode){

	if(typeof(mode)=='undefined')mode=1;

	if(elem.tagName.toLowerCase()!='form'){

		if(mode==0){

			$(elem).removeClass('err');

		}else if(mode==1){

			$(elem).closest('.inp-text, .inp-textarea').removeClass('error');

		}	

	}else{

		$(elem).find('input, textarea').each(function(index,input){

			checkResetStatus(input);

		});

	}

}

function checkPassword(fld,mode){

	if(typeof(mode)=='undefined')mode=1;

	var reg=/[A-z0-9\.]+/;

	var str=fld.value;

	if(reg.test(str))return true;

	else{

		if(mode==0){

			$(fld).addClass('err');

		}else if(mode==1){

			$(fld).closest('.inp-text, .inp-textarea').addClass('error');

		}

		return false;

	}

}

function checkTheSame(fld,mode){

	if(typeof(mode)=='undefined')mode=1;

	var fld2=$('#'+fld.id+'_repeat').get(0);

	if(fld.value==fld2.value)return true;

	else{

		if(mode==0){

			$(fld).addClass('err');

		}else if(mode==1){

			$(fld).closest('.inp-text, .inp-textarea').addClass('error');

		}

		return false;

	}
}

// 1 - not empty

// 2 - e-mail

// 3 - phone

// 4 - date

// 5 - password

// 6 - the same

function checkElements(elements,patterns,mode){

	if(typeof(mode)=='undefined')mode=0;

	var correct=true;

	for(var i=0;i<elements.length;i++){

		if(patterns[i][1] && !checkNotEmpty(elements[i],mode)){

			correct=false;

		}else{

			if(patterns[i][2] && !checkEmail(elements[i],mode)){

				correct=false;

			}

			if(patterns[i][3] && !checkPhone(elements[i],mode)){

				correct=false;

			}

			if(patterns[i][4] && !checkDate(elements[i],mode)){

				correct=false;

			}

			if(patterns[i][5] && !checkPassword(elements[i],mode)){

				correct=false;

			}

			if(patterns[i][6] && !checkTheSame(elements[i],mode)){

				correct=false;

			}

		}

	}

	return correct;

} 


(function ($) {
	$.fn.lightTabs = function() {
		var showTab = function(tab, saveHash) {
			if (!$(tab).hasClass('tab-act')) {
				var tabs = $(tab).closest('.tabs');

				var target_id = $(tab).attr('href');
		        var old_target_id = $(tabs).find('.tab-act').attr('href');
		        $(target_id).show();
		        $(old_target_id).hide();
		        $(tabs).find('.tab-act').removeClass('tab-act');
		        $(tab).addClass('tab-act');

		        if (typeof(saveHash) != 'undefined' && saveHash) history.pushState(null, null, target_id);
			}
		}

		var initTabs = function() {
            var tabs = this;
            
            $(tabs).find('a').each(function(i, tab){
                $(tab).click(function(e) {
                	e.preventDefault();

                	showTab(this, true);
                	fadeoutInit();

                	return false;
                });
                if (i == 0) showTab(tab);                
                else $($(tab).attr('href')).hide();
            });	

            $(tabs).swipe({
				swipeStatus: function(event, phase, direction, distance) {
					var offset = distance;

					if (phase === $.fn.swipe.phases.PHASE_START) {
						var origPos = $(this).scrollLeft();
						$(this).data('origPos', origPos);

					} else if (phase === $.fn.swipe.phases.PHASE_MOVE) {
						var origPos = $(this).data('origPos');

						if (direction == 'left') {
							var scroll_max = $(this).prop('scrollWidth') - $(this).width();
							var scroll_value_new = origPos - 0 + offset;
							$(this).scrollLeft(scroll_value_new);
							if (scroll_value_new >= scroll_max) $(this).addClass('scrolled-full');
							else $(this).removeClass('scrolled-full');

						} else if (direction == 'right') {
							var scroll_value_new = origPos - offset;
							$(this).scrollLeft(scroll_value_new);
							$(this).removeClass('scrolled-full');
						}

					} else if (phase === $.fn.swipe.phases.PHASE_CANCEL) {
						var origPos = $(this).data('origPos');
						$(this).scrollLeft(origPos);

					} else if (phase === $.fn.swipe.phases.PHASE_END) {
						$(this).data('origPos', $(this).scrollLeft());
					}
				},
				threshold: 70
			});	
        };

        return this.each(initTabs);
    };

	$(function () {
		initElements();
		onResize();

		if (typeof(WOW) != 'undefined') {
			new WOW().init();
		}

		$('.js-anchor').click(function(e) {
			e.preventDefault();
			_scrollTo($(this).attr('href'), -$(window).height()/12);
		});

		// BURGER
		/*
		$('nav').click(function() {
			if (!$('html').hasClass('html-mobile-opened')) {
				if (!$(this).children('.close').data('inited')) {
					$(this).children('.close').click(function(e) {
						e.stopPropagation();
						$('html').removeClass('html-mobile-opened html-mobile-long');
						$('.modal-fadeout').stop().fadeOut(300);
					}).data('inited', true);
				}

				$('html').addClass('html-mobile-opened');
				$('header .menu-holder').scrollTop(0).find('.close').stop().show();

				if ($(this).children('ul').outerHeight() > $(window).height()) {
					$('html').addClass('html-mobile-long');
				} else {
					$('html').removeClass('html-mobile-long');
				}

				$('.modal-fadeout').stop().fadeIn(300);
			}
		});
		*/

		// LIGHTBOXES
		var galleries = new Array();
		$('.js-lightbox').each(function(i, a) {
			if (!$(a).is('[data-gallery]')) {
				$(a).magnificPopup({
					type: 'image',
					removalDelay: 300,
					callbacks: {
				        beforeOpen: function() {
				            $(this.contentContainer).removeClass('fadeOutUpBig').addClass('animated fadeInDownBig');
				        },
				        beforeClose: function() {
				        	$(this.contentContainer).removeClass('fadeInDownBig').addClass('fadeOutUpBig');
				        }
				    },
					midClick: true
				});
			} else {
				if (typeof(galleries[$(a).attr('data-gallery')]) == 'undefined') galleries.push($(a).attr('data-gallery'));
			}
		});
		$.each(galleries, function(i, gallery) {
			$('.js-lightbox[data-gallery="' + gallery + '"]').magnificPopup({
				type: 'image',
				removalDelay: 300,
				callbacks: {
			        beforeOpen: function() {
			            $(this.contentContainer).removeClass('fadeOutUpBig').addClass('animated fadeInDownBig');
			        },
			        beforeClose: function() {
			        	$(this.contentContainer).removeClass('fadeInDownBig').addClass('fadeOutUpBig');
			        }
			    },
				gallery: {
					enabled: true
				},
				midClick: true
			});
		});

		$('[data-modal]').each(function(index, object) {
			$(object).click(function(e) {
				if (this.tagName.toLowerCase() == 'a') {
					e.preventDefault();
				}
				showModal($(this).attr('data-modal'));
				var $modal = $('#' + $(this).attr('data-modal'));
				if ($modal.find('form input').filter('[type="text"], [type="tel"], [type="email"]').length) {
					$modal.find('form input').filter('[type="text"], [type="tel"], [type="email"]').get(0).focus();
				}
			});
		});

		$('#feedback form input').each(function() {
			//if ()
		});

		$('#feedback form').on('submit', function(e) {
			e.preventDefault();

            ym(31289493, 'reachGoal', 'sendFeedbackFormBottom');

			var form = this;
			msgUnset(form);
			checkResetStatus(form,0);
			if(checkElements([form.name, form.tel],[{1:true}, {1:true}])){
				form.submit_btn.disabled=true;
				var waitNode=msgSetWait(form);

				$(form).append('<input type="hidden" name="capcha" value="' + navigator.userAgent + '"/>');
					
				$.ajax({
					type: $(form).attr('method'),
					url: $(form).attr('action'),
					data: $(form).serialize(),
					dataType: 'json',
					success: function(response){									
						if(response.status==true){			
							showModal('modal-done');
							form.reset();
								
						}else{
							msgSetError(form,response.error);
						}
						$(waitNode).remove();
						form.submit_btn.disabled=false;
					}
				});
			}else{
				msgSetError(form,'Пожалуйста, заполните все поля');
			}
		});

		// ФОРМЫ ОБРАТНОЙ СВЯЗИ (.modal-ask): проверка полей, вложения, отправка одним запросом с прогрессом
		$('.modal-ask').each(function() {
			var $modal = $(this);
			var $form = $modal.find('form');
			var form = $form.get(0);
			var $contents = $modal.find('.contents');
			var $list = $modal.find('.ask-list');
			var $input = $modal.find('.ask-file-input');
			var $attach = $modal.find('.ask-attach');
			var $err = $modal.find('.ask-error');
			var $submit = $modal.find('.ask-submit');
			var $submitText = $submit.children('span');
			var submitLabel = $submitText.text();
			var modalId = $modal.attr('id');
			var ev = $modal.attr('data-ev') || 'ask';
			var okGoal = $modal.attr('data-goal') || 'sendFeedbackForm';

			var MAX_FILES = 5;
			var MAX_FILE = 10 * 1024 * 1024;
			var MAX_TOTAL = 25 * 1024 * 1024;
			var EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'png', 'jpg', 'jpeg', 'webp', 'svg', 'txt', 'csv', 'rtf'];
			var files = [];
			var sending = false;
			var dragDepth = 0;

			function goal(name) {
				try { ym(31289493, 'reachGoal', name); } catch (e) {}
			}

			function fmtSize(bytes) {
				if (bytes >= 1048576) return (bytes / 1048576).toFixed(1).replace('.', ',') + ' МБ';
				return Math.max(1, Math.round(bytes / 1024)) + ' КБ';
			}

			function fileExt(name) {
				var m = /\.([^.]+)$/.exec(name);
				return m ? m[1].toLowerCase() : '';
			}

			function filesTotal() {
				var sum = 0;
				for (var i = 0; i < files.length; i++) sum += files[i].size;
				return sum;
			}

			function setError(text) {
				$err.text(text || '');
			}

			function render() {
				$list.empty();
				$.each(files, function(i, f) {
					var $li = $('<li>');
					$li.append($('<span class="nm">').text(f.name));
					$li.append($('<span class="sz">').text(fmtSize(f.size)));
					$li.append($('<button type="button" class="rm">').attr({ 'data-i': i, 'aria-label': 'Убрать файл ' + f.name }));
					$list.append($li);
				});
				$attach.prop('disabled', files.length >= MAX_FILES || sending);
			}

			function addFiles(list) {
				var rejected = [];
				var added = 0;
				$.each(list, function(i, f) {
					var why = '';
					var dup = false;
					$.each(files, function(j, x) {
						if (x.name == f.name && x.size == f.size) dup = true;
					});
					if (dup) return;
					if (files.length >= MAX_FILES) why = 'не больше ' + MAX_FILES + ' файлов';
					else if ($.inArray(fileExt(f.name), EXT) < 0) why = 'такой формат не принимаем';
					else if (f.size === 0) why = 'файл пустой';
					else if (f.size > MAX_FILE) why = 'больше 10 МБ';
					else if (filesTotal() + f.size > MAX_TOTAL) why = 'общий размер больше 25 МБ';
					if (why) rejected.push('«' + f.name + '» — ' + why);
					else { files.push(f); added++; }
				});
				setError(rejected.length ? 'Не прикрепили: ' + rejected.join('; ') + '.' : '');
				if (added) goal(ev + '_file_attached');
				if (rejected.length) goal(ev + '_file_error');
				render();
			}

			// поля
			function fieldOf(el) {
				return $(el).closest('.ask-fld, .ask-agree');
			}

			function markInvalid(el, msg) {
				var $f = fieldOf(el);
				$f.addClass('invalid');
				$(el).attr('aria-invalid', 'true');
				$f.find('.msg').remove();
				if (msg && $f.hasClass('ask-fld')) {
					var id = 'msg-' + (el.id || el.name);
					$f.append($('<div class="msg">').attr('id', id).text(msg));
					$(el).attr('aria-describedby', id);
				}
			}

			function markValid(el) {
				var $f = fieldOf(el);
				$f.removeClass('invalid').find('.msg').remove();
				$(el).removeAttr('aria-invalid aria-describedby');
			}

			function checkField(el) {
				var v = $.trim($(el).val());
				var msg = '';
				if (el.name == 'name') {
					if (!v) msg = 'Подскажите, как к вам обращаться';
				} else if (el.name == 'tel') {
					var digits = v.replace(/\D/g, '').length;
					if (!digits) msg = 'Оставьте телефон, чтобы мы могли ответить';
					else if (digits < 11) msg = 'Похоже, в номере не хватает цифр';
				} else if (el.name == 'text') {
					if (!v && $(el).prop('required')) msg = 'Напишите, что хотите узнать';
				}
				if (msg) markInvalid(el, msg);
				else markValid(el);
				return !msg;
			}

			function validate() {
				var ok = true;
				var first = null;
				$.each($.grep([form.name, form.tel, form.text], function(el) { return el && el.tagName; }), function(i, el) {
					if (!checkField(el)) {
						ok = false;
						if (!first) first = el;
					}
				});
				if (!form.agree.checked) {
					markInvalid(form.agree);
					setError('Нужно согласие на обработку данных.');
					ok = false;
					if (!first) first = form.agree;
				}
				if (first) first.focus();
				return ok;
			}

			$form.find('input[name="name"], input[name="tel"], textarea[name="text"]').on('blur', function() {
				if ($.trim($(this).val()) || fieldOf(this).hasClass('invalid')) checkField(this);
			}).on('input', function() {
				if (fieldOf(this).hasClass('invalid')) markValid(this);
			});

			$(form.agree).on('change', function() {
				if (this.checked) {
					markValid(this);
					if ($err.text().indexOf('согласие') >= 0) setError('');
				}
			});

			// вложения
			$attach.on('click', function() {
				$input.get(0).click();
			});
			$input.on('change', function() {
				if (this.files && this.files.length) addFiles(this.files);
				this.value = '';
			});
			$list.on('click', '.rm', function() {
				files.splice(parseInt($(this).attr('data-i'), 10), 1);
				setError('');
				render();
				$attach.focus();
			});

			// перетаскивание на всё окно
			function hasFiles(e) {
				var dt = e.originalEvent.dataTransfer;
				return dt && $.inArray('Files', dt.types || []) >= 0;
			}
			// слушаем весь документ: файл, брошенный мимо окна (на плашку cookie, чат), браузер иначе откроет в этой вкладке
			$(document).on('dragenter', function(e) {
				if (!$modal.is(':visible') || !hasFiles(e)) return;
				e.preventDefault();
				dragDepth++;
				$contents.addClass('drag');
			}).on('dragover', function(e) {
				if (!$modal.is(':visible') || !hasFiles(e)) return;
				e.preventDefault();
				e.originalEvent.dataTransfer.dropEffect = 'copy';
				$contents.addClass('drag');
			}).on('dragleave', function(e) {
				if (!$modal.is(':visible') || !hasFiles(e)) return;
				dragDepth = Math.max(0, dragDepth - 1);
				if (!dragDepth) $contents.removeClass('drag');
			}).on('drop', function(e) {
				if (!$modal.is(':visible') || !hasFiles(e)) return;
				e.preventDefault();
				dragDepth = 0;
				$contents.removeClass('drag');
				if (!sending) addFiles(e.originalEvent.dataTransfer.files);
			}).on('dragend', function() {
				dragDepth = 0;
				$contents.removeClass('drag');
			});

			// вставка скриншота из буфера
			$(document).on('paste', function(e) {
				if (!$modal.is(':visible') || sending) return;
				var cd = e.originalEvent.clipboardData;
				if (cd && cd.files && cd.files.length) {
					e.preventDefault();
					addFiles(cd.files);
				}
			});

			// Esc закрывает окно
			$(document).on('keydown', function(e) {
				if (e.keyCode == 27 && $modal.is(':visible') && !sending) {
					closeModal($modal.find('.modal-close').get(0));
				}
			});

			function finish() {
				sending = false;
				$submit.removeClass('loading').css('--p', '0%').prop('disabled', false);
				$submitText.text(submitLabel);
				render();
			}

			$form.on('submit', function(e) {
				e.preventDefault();
				if (sending) return;
				setError('');
				if (!validate()) return;

				var fd = new FormData(form);
				fd.append('capcha', navigator.userAgent);
				$.each(files, function(i, f) {
					fd.append('files[]', f, f.name);
				});

				sending = true;
				$submit.addClass('loading').prop('disabled', true);
				$submitText.text('Отправляем…');
				render();

				var xhr = new XMLHttpRequest();
				xhr.open('POST', form.getAttribute('action'));
				xhr.timeout = 180000;
				xhr.upload.onprogress = function(ev) {
					if (!files.length || !ev.lengthComputable) return;
					var pct = Math.round(ev.loaded / ev.total * 100);
					$submit.css('--p', pct + '%');
					$submitText.text(pct < 100 ? 'Отправляем… ' + pct + '%' : 'Обрабатываем…');
				};
				xhr.onload = function() {
					var response = null;
					try { response = JSON.parse(xhr.responseText); } catch (err) {}
					if (response && response.status === true) {
						goal(okGoal);
						goal(ev + '_success');
						form.reset();
						files = [];
						finish();
						$modal.stop().hide();
						showModal('modal-done');
					} else {
						goal(ev + '_error');
						setError((response && (response.message || response.error)) || 'Не удалось отправить. Попробуйте ещё раз или напишите нам на почту.');
						finish();
					}
				};
				xhr.onerror = xhr.ontimeout = function() {
					goal(ev + '_error');
					setError('Не удалось отправить: нет связи с сервером. Всё, что вы ввели, сохранилось — попробуйте ещё раз.');
					finish();
				};
				xhr.send(fd);
			});

			// открытие окна
			$('[data-modal="' + modalId + '"]').on('click', function() {
				goal(ev + '_open');
			});
		});

        $('header .btn, #direction .actions .btn').click(function() {
                ym(31289493, 'reachGoal', 'clickFeedbackButton');
        });

        $('#services [data-modal="modal-question"], #faq [data-modal="modal-question"], #process [data-modal="modal-question"]').click(function() {
                ym(31289493, 'reachGoal', 'clickQuestionButton');
        });

        $('.js-year').text(new Date().getFullYear());

        // KNOW BASE
        if ($('.knowbase').length) {
        	$('.knowbase .search form').on('submit', function(e) {
        		e.preventDefault();
        		var keyword = $(this).children('input[type="text"]').val();
        		redirect(keyword ? ('/knowbase-keyword-' + keyword + '/') : '/knowbase/');
        	});
        }
	})
})(jQuery);


// COOKIE NOTICE
(function ($) {
	var key = 'cookieNoticeAccepted';
	try {
		if (localStorage.getItem(key)) return;
	} catch (e) {}

	$(function () {
		var $notice = $('#cookie-notice');
		if (!$notice.length) return;

		setTimeout(function () {
			$notice.addClass('visible');
		}, 2500);

		$notice.find('.btn').click(function () {
			try {
				localStorage.setItem(key, '1');
			} catch (e) {}
			$notice.removeClass('visible');
		});
	});
})(jQuery);


// MODALS: закрытие по Esc и по клику в пустую область
(function ($) {
	var downOutside = false;

	$(document).on('keydown', function (e) {
		if ((e.key === 'Escape' || e.keyCode === 27) && $('.modal-wrapper:visible').length) {
			if ($.magnificPopup && $.magnificPopup.instance && $.magnificPopup.instance.isOpen) return;
			closeModal();
		}
	});

	// закрываем, только если и нажатие, и отпускание были вне окна (чтобы не закрывать при выделении текста)
	$(document).on('mousedown', '.modal-wrapper', function (e) {
		downOutside = !$(e.target).closest('.contents').length;
	}).on('click', '.modal-wrapper', function (e) {
		if (downOutside && !$(e.target).closest('.contents').length) {
			closeModal(this);
		}
		downOutside = false;
	});
})(jQuery);


// WORKS: ссылка внутри карточки-ссылки (вложенный <a> невалиден)
(function ($) {
	function go(el, e) {
		e.preventDefault();
		e.stopPropagation();
		window.open($(el).data('href'), '_blank', 'noopener');
	}
	$(document).on('click', '.work .lnk', function (e) {
		go(this, e);
	}).on('keydown', '.work .lnk', function (e) {
		if (e.key === 'Enter' || e.key === ' ') go(this, e);
	});
})(jQuery);


// WORKS: «Показать ещё» на узких экранах
(function ($) {
	$(function () {
		var $works = $('#portfolio .works');
		if (!$works.length) return;
		$works.addClass('collapsed');
		$('#portfolio .works-more').on('click', function () {
			$works.removeClass('collapsed');
			$(this).parent().remove();
		});
	});
})(jQuery);


// CTA: спутник облетает планету по наклонной орбите
(function ($) {
	$(function () {
		var deco = document.querySelector('#services .cta .deco');
		var drop = deco && deco.querySelector('.drop');
		if (!drop) return;

		var A = 225, B = 70, TILT = 35 * Math.PI / 180, COS = Math.cos(TILT), SIN = Math.sin(TILT);
		var T0 = 55 * Math.PI / 180;      // стартовое положение совпадает с CSS (top/left)
		var PERIOD = 11000;               // мс на оборот

		function pos(t) {
			var lx = A * Math.cos(t), ly = B * Math.sin(t);
			return { x: lx * COS + ly * SIN, y: -lx * SIN + ly * COS, d: Math.sin(t) };
		}
		var p0 = pos(T0);

		var S = 1;                         // масштаб относительно эталона 480px
		function measure() { S = deco.offsetWidth / 480 || 1; }
		measure();
		$(window).on('resize', measure);

		function place(t) {
			var p = pos(t);
			var k = 0.74 + 0.26 * (p.d + 1) / 2;       // дальняя половина орбиты меньше
			drop.style.transform = 'translate(' + ((p.x - p0.x) * S).toFixed(1) + 'px,' + ((p.y - p0.y) * S).toFixed(1) + 'px) scale(' + k.toFixed(3) + ')';
			drop.style.filter = 'brightness(' + (0.78 + 0.22 * (p.d + 1) / 2).toFixed(2) + ')';   // не opacity: иначе орбита просвечивает
			drop.style.zIndex = p.d >= 0 ? 4 : 1;      // сзади — под планетой, спереди — над ней
		}

		if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

		var raf = 0, t0 = 0, base = T0, phase = T0;
		function frame(now) {
			if (!t0) t0 = now;
			phase = base + (now - t0) / PERIOD * Math.PI * 2;
			place(phase);
			raf = requestAnimationFrame(frame);
		}
		function start() {
			if (raf) return;
			t0 = 0;
			raf = requestAnimationFrame(frame);
		}
		function stop() {
			if (!raf) return;
			cancelAnimationFrame(raf);
			raf = 0;
			base = phase;   // продолжим с того же места
		}

		if ('IntersectionObserver' in window) {
			new IntersectionObserver(function (en) {
				en[0].isIntersecting ? start() : stop();
			}).observe(deco);
		} else {
			start();
		}
	});
})(jQuery);


// TO TOP
(function ($) {
	$(function () {
		var $btn = $('#to-top');
		if (!$btn.length) return;

		var toggle = function () {
			$btn.toggleClass('visible', $(window).scrollTop() > 700);
		};
		$(window).on('scroll', toggle);
		toggle();

		$btn.on('click', function () {
			$('html, body').animate({scrollTop: 0}, 700);
		});
	});
})(jQuery);


// PROCESS: этапы появляются слева направо
(function ($) {
	$(function () {
		var $steps = $('#process .steps');
		if (!$steps.length) return;
		$steps.find('.step').each(function (i) {
			this.style.setProperty('--i', i);
		});
		if (!('IntersectionObserver' in window)) return;
		$steps.addClass('armed');
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (en.isIntersecting) {
					$steps.addClass('in');
					// подсвечиваем этапы по очереди, пока они появляются
					$steps.find('.step').each(function (i) {
						var $step = $(this);
						setTimeout(function () {
							$step.addClass('active');
							setTimeout(function () { $step.removeClass('active'); }, 800);
						}, i * 450 + 150);
					});
					setTimeout(function () { $steps.addClass('done'); }, $steps.find('.step').length * 450 + 1000);
					io.disconnect();
				}
			});
		}, {threshold: 0.25});
		io.observe($steps[0]);
	});
})(jQuery);

// HERO: сетка точек с волнами от курсора и кликов
(function ($) {
	$(function () {
		var hero = document.getElementById('direction');
		var cv = hero && hero.querySelector('.waves');
		if (!cv || !cv.getContext) return;
		var ctx = cv.getContext('2d');
		var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		var DPR = Math.min(window.devicePixelRatio || 1, 2);
		var noHover = window.matchMedia && window.matchMedia('(hover: none)').matches;
		var S = 30, W = 0, H = 0, cols = 0, rows = 0, mx = -9999, my = -9999, rip = [], last = 0, visible = true, running = false;

		function size() {
			var r = cv.getBoundingClientRect();
			W = r.width; H = r.height;
			cv.width = Math.max(1, Math.round(W * DPR));
			cv.height = Math.max(1, Math.round(H * DPR));
			cols = Math.ceil(W / S) + 1;
			rows = Math.ceil(H / S) + 1;
			render(performance.now());
		}

		function render(now) {
			ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
			ctx.clearRect(0, 0, W, H);
			rip = rip.filter(function (r) { return now - r.t < 3200; });
			for (var i = 0; i < cols; i++) {
				for (var j = 0; j < rows; j++) {
					var x = i * S + 10, y = j * S + 10, k = 0;
					var dx = x - mx, dy = y - my, d = Math.sqrt(dx * dx + dy * dy);
					if (d < 150) k = 1 - d / 150;
					for (var q = 0; q < rip.length; q++) {
						var age = (now - rip[q].t) / 1000, ex = x - rip[q].x, ey = y - rip[q].y;
						var ed = Math.sqrt(ex * ex + ey * ey);
						var w = Math.exp(-Math.pow((ed - age * 380) / 55, 2)) * (1 - age / 3.2);
						if (w > k) k = w;
					}
					ctx.fillStyle = k > 0.04 ? 'rgba(23,113,248,' + (0.35 + k * 0.65) + ')' : 'rgba(21,23,28,0.2)';
					ctx.beginPath();
					ctx.arc(x, y, 1.7 + k * 5.5, 0, 6.2832);
					ctx.fill();
				}
			}
		}

		function loop() {
			if (!visible) { running = false; return; }
			var now = performance.now();
			if (now - last > 3800) {
				if (mx > -999) {
					last = now;
					rip.push({x: mx, y: my, t: now});
				} else if (noHover) {
					last = now;
					rip.push({x: W * 0.72, y: H * 0.45, t: now});
				}
			}
			render(now);
			requestAnimationFrame(loop);
		}

		function start() {
			if (running || reduce) return;
			running = true;
			loop();
		}

		size();
		$(window).on('resize', size);
		var entered = false;
		if (reduce) {
			rip.push({x: W * 0.72, y: H * 0.45, t: performance.now() - 900});
			render(performance.now());
			return;
		}
		$(hero).on('pointermove pointerdown', function (e) {
			var r = cv.getBoundingClientRect();
			mx = e.clientX - r.left;
			my = e.clientY - r.top;
			if (e.type === 'pointerdown') {
				last = performance.now();
				rip.push({x: mx, y: my, t: last});
			}
			else if (!entered) {
				// первое появление курсора в hero: волна под ним (если стартовая только что вышла — не дублируем)
				entered = true;
				var n = performance.now();
				if (n - last > 2500) {
					last = n;
					rip.push({x: mx, y: my, t: n});
				}
			}
		});
		hero.addEventListener('pointerleave', function (e) {
			// курсор реально ушёл за границы hero (а не перекрыт плавающим блоком вроде чата)
			var r = hero.getBoundingClientRect();
			if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) {
				mx = my = -9999;
				entered = false;
			}
		});
		if ('IntersectionObserver' in window) {
			new IntersectionObserver(function (en) {
				visible = en[0].isIntersecting;
				if (visible) start();
			}).observe(hero);
		}
		// стартовая волна: показывает, что сетка живая и реагирует
		// (небольшая задержка: если курсор уже над hero, волна пойдёт из-под него)
		start();
		setTimeout(function () {
			last = performance.now();
			rip.push(mx > -999 ? {x: mx, y: my, t: last} : {x: W * 0.72, y: H * 0.45, t: last});
			entered = mx > -999;
		}, 500);
	});
})(jQuery);
