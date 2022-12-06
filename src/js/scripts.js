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

	$element.find('input[type="checkbox"], input[type="radio"]').checkboxradio(); 

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

function showModal(modal_id, dontHideOthers) {
	var $modal = $('#' + modal_id);

	if (typeof(dontHideOthers) == 'undefined' || !dontHideOthers) $('.modal-wrapper:visible').not($modal).attr('data-transparent', true).stop().animate({'opacity': 0}, __animationSpeed);

	$('.modal-fadeout').stop().fadeIn(300);
	$modal.stop().fadeIn(450).css({
		'display': __isMobileTablet ? 'block' : 'table',
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

		$('[data-modal]').each(function(index, object) {
			$(object).click(function(e) {
				if (this.tagName.toLowerCase == 'a') {
					e.preventDefault();
				}
				showModal($(this).attr('data-modal'));
			});
		});

		$('#services ul>li').click(function() {
			if (!$(this).hasClass('opened')) {
				$(this).addClass('opened')
					.children('.desc').stop().slideDown(__animationSpeed);
			}
		});
		$('#services ul>li>h3, #services ul>li>.exp, #services ul>li>.toggler').click(function(e) {
			var $li = $(this).closest('li');
			if ($li.hasClass('opened')) {
				e.stopPropagation();
				$li.removeClass('opened')
					.children('.desc').stop().slideUp(__animationSpeed);
			}
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

		$('#modal-feedback form').on('submit', function(e) {
			e.preventDefault();

            ym(31289493, 'reachGoal', 'sendFeedbackForm');
			
			var form = this;
			msgUnset(form);
			checkResetStatus(form, 0);
			if (checkElements([form.name, form.tel],[{1:true}, {1:true}])) {
				form.submit_btn.disabled = true;
				var waitNode = msgSetWait(form);

				$(form).append('<input type="hidden" name="capcha" value="' + navigator.userAgent + '"/>');
					
				$.ajax({
					type: $(form).attr('method'),
					url: $(form).attr('action'),
					data: $(form).serialize(),
					dataType: 'json',
					success: function(response){									
						if(response.status == true){
							showModal('modal-done');
							form.reset();
								
						}else{
							msgSetError(form,response.error);
						}
						$(waitNode).remove();
						form.submit_btn.disabled = false;
					}
				});
			}else{
				msgSetError(form, 'Пожалуйста, заполните все поля');
			}
		});

		$('#modal-question form').on('submit', function(e) {
			e.preventDefault();

            ym(31289493, 'reachGoal', 'sendQuestionForm');
			
			var form = this;
			msgUnset(form);
			checkResetStatus(form, 0);
			if (checkElements([form.name, form.tel],[{1:true}, {1:true}])) {
				form.submit_btn.disabled = true;
				var waitNode = msgSetWait(form);

				$(form).append('<input type="hidden" name="capcha" value="' + navigator.userAgent + '"/>');
					
				$.ajax({
					type: $(form).attr('method'),
					url: $(form).attr('action'),
					data: $(form).serialize(),
					dataType: 'json',
					success: function(response){									
						if(response.status == true){
							showModal('modal-done');
							form.reset();
								
						}else{
							msgSetError(form,response.error);
						}
						$(waitNode).remove();
						form.submit_btn.disabled = false;
					}
				});
			}else{
				msgSetError(form, 'Пожалуйста, заполните все поля');
			}
		});

		$('#modal-request form').on('submit', function(e) {
			e.preventDefault();

            ym(31289493, 'reachGoal', 'sendFeedbackForm');
			
			var form = this;
			msgUnset(form);
			checkResetStatus(form, 0);
			if (checkElements([form.name, form.tel],[{1:true}, {1:true}])) {
				form.submit_btn.disabled = true;
				var waitNode = msgSetWait(form);

				$(form).append('<input type="hidden" name="capcha" value="' + navigator.userAgent + '"/>');
					
				$.ajax({
					type: $(form).attr('method'),
					url: $(form).attr('action'),
					data: $(form).serialize(),
					dataType: 'json',
					success: function(response){									
						if(response.status == true){
							showModal('modal-done');
							form.reset();
								
						}else{
							msgSetError(form,response.error);
						}
						$(waitNode).remove();
						form.submit_btn.disabled = false;
					}
				});
			}else{
				msgSetError(form, 'Пожалуйста, заполните все поля');
			}
		});

        $('header .btn').click(function() {
                ym(31289493, 'reachGoal', 'clickFeedbackButton');
        });

        $('#services ul>li>.desc>.btn-line>.btn').click(function() {
                ym(31289493, 'reachGoal', 'clickQuestionButton');
        });
	})
})(jQuery)
