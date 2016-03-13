/*! The MIT License (MIT)

Copyright (c) 2015 Senthil Porunan<senthilraja39@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

Source: https://github.com/senthilporunan/jRate
Demo: http://www.toolitup.com/JRate.html
**/
;
(function($, undefined) {

    $.fn.jRate = function(options) {

        "use strict";
        var $jRate = $(this);
        var defaults = {
            rating: 0,
            shape: "STAR",
            count: 5,
            width: "20",
            height: "20",
            widthGrowth: 0.0,
            heightGrowth: 0.0,
            backgroundColor: "white",
            startColor: "yellow",
            endColor: "green",
            strokeColor: "black",
            transparency: 1,
            shapeGap: "0px",
            opacity: 1,
            min: 0,
            max: 5,
            precision: 0.1,
            minSelected: 0,
			strokeWidth: '2px',
            horizontal: true,
            reverse: false,
            readOnly: false,
			touch: true,
            onChange: null,
            onSet: null
        };
        var settings = $.extend({}, defaults, options);
        var startColorCoords, endColorCoords, shapes;

        function isDefined(name) {
            return typeof name !== "undefined";
        }

        function getRating() {
            if (isDefined(settings))
                return settings.rating;
        }

        function setRating(rating) {
            if (!isDefined(rating) || rating < settings.min || rating > settings.max)
                throw rating + " is not in range(" + min + "," + max + ")";
			settings.rating = rating;
            showRating(rating);
        }

        function getShape(currValue) {
            var header = '<svg width="' + settings.width + '" height=' + settings.height + ' xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"';
            var hz = settings.horizontal;
			var id = $jRate.attr('id');
            var linearGrad = '<defs><linearGradient id="'+id+'_grad'+currValue+'" x1="0%" y1="0%" x2="' + (hz ? 100 : 0) + '%" y2="' + (hz ? 0 : 100) + '%">' +
                '<stop offset="0%"  stop-color=' + settings.backgroundColor + '/>' +
                '<stop offset="0%" stop-color=' + settings.backgroundColor + '/>' +
                '</linearGradient></defs>';
            var shapeRate;
            switch (settings['shape']) {
                case 'STAR':
                    shapeRate = header + 'viewBox="0 12.705 512 486.59"' + '>' + linearGrad + '<polygon style="fill: url(#'+id+'_grad'+currValue+');stroke:' + settings.strokeColor + ';fill-opacity:' + +settings.transparency + ';stroke-width:' + settings.strokeWidth + '; "points="256.814,12.705 317.205,198.566' + ' 512.631,198.566 354.529,313.435 ' + '414.918,499.295 256.814,384.427 ' + '98.713,499.295 159.102,313.435 ' + '1,198.566 196.426,198.566 "/>' + '</svg>';
                    break;
                case 'CIRCLE':
                    shapeRate = header + '>' + linearGrad + '<circle  cx="' + settings.width / 2 + '" cy="' + settings.height / 2 + '" r="' + settings.width / 2 + '" fill="url(#'+id+'_grad'+currValue+')" style="stroke:' + settings.strokeColor + ';fill-opacity:' + +settings.transparency + ';stroke-width:' + settings.strokeWidth + ';"/>' + '</svg>';
                    break;
                case 'RECTANGLE':
                    shapeRate = header + '>' + linearGrad + '<rect width="' + settings.width + '" height="' + settings.height + '" fill="url(#'+id+'_grad'+currValue+')" style="stroke:' + settings.strokeColor + ';fill-opacity:' + +settings.transparency + ';stroke-width:' + settings.strokeWidth + ';"/>' +
                        '</svg>';
                    break;
                case 'TRIANGLE':
                    shapeRate = header + '>' + linearGrad +
                        '<polygon points="' + settings.width / 2 + ',0 0,' + settings.height + ' ' + settings.width + ',' + settings.height + '" fill="url(#'+id+'_grad'+currValue+')" style="stroke:' + settings.strokeColor + ';fill-opacity:' + +settings.transparency + ';stroke-width:'+ settings.strokeWidth + ';"/>' +
                        '</svg>';
                    break;
                case 'RHOMBUS':
                    shapeRate = header + '>' + linearGrad + '<polygon points="' + settings.width / 2 + ',0 ' + settings.width + ',' + settings.height / 2 + ' ' + settings.width / 2 + ',' + settings.height + ' 0,' + settings.height / 2 + '" fill="url(#'+id+'_grad'+currValue+')"  style="stroke:' + settings.strokeColor + ';fill-opacity:' + +settings.transparency + ';stroke-width:'+ settings.strokeWidth + ';"/>' + '</svg>';
                    break;
				case 'FOOD':
					shapeRate = header + 'viewBox="0 0 50 50"' + '>' + linearGrad + 
					'<path fill="url(#'+id+'_grad'+currValue+')" style="stroke:' + settings.strokeColor + ';fill-opacity:' + +settings.transparency + ';"' + 
					'd="M45.694,21.194C45.694,9.764,36.43,0.5,25,0.5S4.306,9.764,4.306,21.194c0,8.621,5.272,16.005,12.764,19.115'+
					'c-1.882,2.244-3.762,4.486-5.645,6.73c-0.429,0.5-0.458,1.602,0.243,2.145c0.7,0.551,1.757,0.252,2.139-0.289'+
					'c1.878-2.592,3.753-5.189,5.63-7.783c1.774,0.494,3.633,0.777,5.561,0.777c1.85,0,3.64-0.266,5.349-0.723'+
					'c1.617,2.563,3.238,5.121,4.862,7.684c0.34,0.555,1.365,0.91,2.088,0.414c0.728-0.492,0.759-1.58,0.368-2.096'+
					'c-1.663-2.252-3.332-4.508-4.995-6.76C40.3,37.354,45.694,29.91,45.694,21.194z M25,37.824c-1.018,0-2.01-0.105-2.977-0.281'+
					'c1.07-1.48,2.146-2.965,3.215-4.447c0.939,1.48,1.874,2.959,2.81,4.436C27.058,37.717,26.041,37.824,25,37.824z M30.155,37'+
					'c-1.305-1.764-2.609-3.527-3.91-5.295c0.724-1,1.446-1.998,2.17-3c1.644,0.746,3.646,0,4.827-1.787c1.239-1.872,0.005,0,0.005,0.002'+
					'c0.01-0.014,5.822-8.824,5.63-8.97c-0.186-0.15-3.804,4.771-6.387,8.081l-0.548-0.43c2.362-3.481,5.941-8.426,5.757-8.575'+
					'c-0.189-0.146-3.959,4.655-6.652,7.878l-0.545-0.428c2.463-3.398,6.202-8.228,6.014-8.374c-0.188-0.15-4.115,4.528-6.917,7.67'+
					'l-0.547-0.43c2.575-3.314,6.463-8.02,6.278-8.169c-0.191-0.15-5.808,6.021-7.319,7.651c-1.325,1.424-1.664,3.68-0.562,5.12'+
					'c-0.703,0.84-1.41,1.678-2.113,2.518c-0.781-1.057-1.563-2.111-2.343-3.17c1.975-1.888,1.984-5.234-0.054-7.626'+
					'c-2.14-2.565-6.331-5.22-8.51-3.818c-2.093,1.526-1.14,6.396,0.479,9.316c1.498,2.764,4.617,3.965,7.094,2.805'+
					'c0.778,1.227,1.554,2.455,2.333,3.684c-1.492,1.783-2.984,3.561-4.478,5.342C13.197,34.826,8.38,28.574,8.38,21.191'+
					'c0-9.183,7.444-16.631,16.632-16.631c9.188,0,16.625,7.447,16.625,16.631C41.63,28.576,36.816,34.828,30.155,37z"/>'+'</svg>';
					break;
				case 'TWITTER':
					shapeRate = header + 'viewBox="0 0 512 512"' + '>' + linearGrad + 
					'<path fill="url(#'+id+'_grad'+currValue+')" style="stroke:' + settings.strokeColor + ';fill-opacity:' + +settings.transparency + ';stroke-width:0.7px;"' + 
					'd="M512,97.209c-18.838,8.354-39.082,14.001-60.33,16.54c21.687-13,38.343-33.585,46.187-58.115'+								 'c-20.299,12.039-42.778,20.78-66.705,25.49c-19.16-20.415-46.461-33.17-76.674-33.17c-58.011,0-105.043,47.029-105.043,105.039'+
					'c0,8.233,0.929,16.25,2.72,23.939c-87.3-4.382-164.701-46.2-216.509-109.753c-9.042,15.514-14.223,33.558-14.223,52.809'+
					'c0,36.444,18.544,68.596,46.73,87.433c-17.219-0.546-33.416-5.271-47.577-13.139c-0.01,0.438-0.01,0.878-0.01,1.321'+
					'c0,50.894,36.209,93.348,84.261,103c-8.813,2.399-18.094,3.686-27.674,3.686c-6.769,0-13.349-0.66-19.764-1.887'+
					'c13.368,41.73,52.16,72.104,98.126,72.949c-35.95,28.175-81.243,44.967-130.458,44.967c-8.479,0-16.84-0.497-25.058-1.471'+
					'c46.486,29.806,101.701,47.197,161.021,47.197c193.211,0,298.868-160.062,298.868-298.872c0-4.554-0.103-9.084-0.305-13.59'+
					'C480.11,136.773,497.918,118.273,512,97.209z"/>'+'</svg>';
					break;
				case 'BULB':
					shapeRate = header + 'viewBox="0 0 512 512"' + '>' + linearGrad + 
					'<path fill="url(#'+id+'_grad'+currValue+')" style="stroke:' + settings.strokeColor + ';fill-opacity:' + +settings.transparency + ';stroke-width:0.7px;"' + 'd="M384,192c0,64-64,127-64,192H192c0-63-64-128-64-192c0-70.688,57.313-128,128-128S384,121.313,384,192z M304,448h-96'+
					'c-8.844,0-16,7.156-16,16s7.156,16,16,16h2.938c6.594,18.625,24.188,32,45.063,32s38.469-13.375,45.063-32H304'+
					'c8.844,0,16-7.156,16-16S312.844,448,304,448z M304,400h-96c-8.844,0-16,7.156-16,16s7.156,16,16,16h96c8.844,0,16-7.156,16-16'+
					'S312.844,400,304,400z M81.719,109.875l28.719,16.563c4.438-9.813,9.844-19,16.094-27.656L97.719,82.125L81.719,109.875z'+
					' M272,33.625V0h-32v33.625C245.344,33.063,250.5,32,256,32S266.656,33.063,272,33.625z M190.438,46.438l-16.563-28.719l-27.75,16'+
					'l16.656,28.813C171.438,56.281,180.625,50.875,190.438,46.438z M430.281,109.875l-16-27.75l-28.813,16.656'+
					'c6.25,8.656,11.688,17.844,16.125,27.656L430.281,109.875z M365.844,33.719l-27.688-16l-16.563,28.719'+
					'c9.781,4.438,19,9.844,27.625,16.063L365.844,33.719z M96,192c0-5.5,1.063-10.656,1.625-16H64v32h33.688'+
					'C97.063,202.688,96,197.438,96,192z M414.375,176c0.563,5.344,1.625,10.5,1.625,16c0,5.438-1.063,10.688-1.688,16H448v-32H414.375z'+
					' M388.094,286.719l26.188,15.125l16-27.719l-29.063-16.75C397.188,267.313,392.813,277.063,388.094,286.719z M81.719,274.125'+
					'l16,27.719l25.969-14.969c-4.688-9.688-9.063-19.5-13.031-29.438L81.719,274.125z"/>'+'</svg>';
					break;
				case 'BASKET':
					shapeRate = header + 'viewBox="0 0 30 30"' + '>' + linearGrad +
					'<path fill="url(#'+id+'_grad'+currValue+')" style="stroke:' + settings.strokeColor + ';fill-opacity:' + +settings.transparency + ';stroke-width:0.7px;"' + 
					'd="M28.835,9.955H7.947L5.74,1.352C5.632,0.795,5.16,0.375,4.595,0.375H1.169C0.523,0.375,0,0.922,0,1.597' +
					'c0,0.673,0.523,1.22,1.169,1.22H3.7l5.312,20.71c-0.404,0.16-0.767,0.407-1.068,0.72v0.002l-0.002-0.002' +
					'c-0.546,0.569-0.884,1.36-0.884,2.228c0,0.868,0.338,1.659,0.884,2.228l0.044,0.044c0.543,0.545,1.28,0.88,2.089,0.88' +
					'c0.831,0,1.588-0.353,2.134-0.924c0.545-0.569,0.883-1.359,0.883-2.228c0-0.612-0.169-1.187-0.46-1.674h8.839' +
					'c-0.292,0.486-0.461,1.062-0.461,1.674c0,0.868,0.338,1.659,0.884,2.228c0.544,0.57,1.301,0.924,2.133,0.924' +
					'c0.831,0,1.585-0.353,2.131-0.924V28.7l0.003,0.001c0.545-0.569,0.883-1.359,0.883-2.228c0-0.617-0.172-1.198-0.467-1.686' +
					'c0.57-0.08,1.008-0.592,1.008-1.208c0-0.675-0.523-1.221-1.169-1.221H11.128l-0.776-3.03h16.77c0.577,0,1.057-0.438,1.152-1.013' +
					'l1.69-6.833c0.161-0.651-0.214-1.316-0.836-1.484c-0.097-0.025-0.197-0.039-0.292-0.039V9.955L28.835,9.955z M10.089,24.788' +
					'c0.048,0.007,0.095,0.01,0.145,0.01V24.8h0.032c0.37,0.045,0.702,0.222,0.95,0.481c0.291,0.305,0.472,0.729,0.472,1.193' +
					'c0,0.466-0.18,0.89-0.472,1.193c-0.29,0.305-0.696,0.493-1.142,0.493c-0.432,0-0.825-0.175-1.113-0.461l-0.029-0.032' +
					'c-0.292-0.303-0.472-0.727-0.472-1.193c0-0.464,0.18-0.888,0.472-1.193H8.931c0.292-0.303,0.697-0.493,1.144-0.493H10.089' +
					'L10.089,24.788z M23.834,24.8h0.383c0.356,0.045,0.677,0.207,0.921,0.449l0.029,0.032c0.291,0.305,0.473,0.729,0.473,1.193' +
					'c0,0.466-0.182,0.89-0.473,1.193l0.001,0.002c-0.291,0.305-0.697,0.491-1.143,0.491c-0.445,0-0.85-0.188-1.142-0.493' +
					'c-0.29-0.303-0.472-0.727-0.472-1.193c0-0.464,0.182-0.888,0.472-1.193C23.132,25.021,23.465,24.845,23.834,24.8L23.834,24.8z"/>'+'</svg>';
					break;
                default:
                    throw Error("No such shape as " + settings['shape']);
            }
			return shapeRate;
        }

        function setCSS() {
            // setup css properies
            $jRate.css("white-space", "nowrap");
            $jRate.css("cursor", "pointer");

            $jRate.css('fill', settings['shape']);
        }

        function bindEvents($svg, i) {
            $svg.on("mousemove", onMouseEnter(i))
                .on("mouseenter", onMouseEnter(i))
				.on("click", onMouseClick(i))
                .on("mouseover", onMouseEnter(i))
                .on("hover", onMouseEnter(i))
                .on("mouseleave", onMouseLeave)
                .on("mouseout", onMouseLeave)
                .on("JRate.change", onChange)
                .on("JRate.set", onSet);
	    if (settings.touch) {
			$svg.on("touchstart", onTouchEnter(i))
				.on("touchmove", onTouchEnter(i))
				.on("touchend", onTouchClick(i))
				.on("tap", onTouchClick(i));
	      }
        }

        function showNormalRating() {
			var id = $jRate.attr('id');
            for (var i = 0; i < settings.count; i++) {
                shapes.eq(i).find('#'+id+'_grad'+(i+1)).find("stop").eq(0).attr({
                    'offset': '0%'
                });
                shapes.eq(i).find('#'+id+'_grad'+(i+1)).find("stop").eq(0).attr({
                    'stop-color': settings.backgroundColor
                });
                shapes.eq(i).find('#'+id+'_grad'+(i+1)).find("stop").eq(1).attr({
                    'offset': '0%'
                });
                shapes.eq(i).find('#'+id+'_grad'+(i+1)).find("stop").eq(1).attr({
                    'stop-color': settings.backgroundColor
                });
            }
        }

        function showRating(rating) {

            showNormalRating();
            var singleValue = (settings.max - settings.min) / settings.count;
            rating = (rating - settings.min) / singleValue;
            var fillColor = settings.startColor;
			var id = $jRate.attr('id');
			
            if (settings.reverse) {
                for (var i = 0; i < rating; i++) {
					var j = settings.count - 1 - i;
                    shapes.eq(j).find('#'+id+'_grad'+(j+1)).find("stop").eq(0).attr({
                        'offset': '100%'
                    });
                    shapes.eq(j).find('#'+id+'_grad'+(j+1)).find("stop").eq(0).attr({
                        'stop-color': fillColor
                    });
                    if (parseInt(rating) !== rating) {
						var k = Math.ceil(settings.count - rating) - 1;
                        shapes.eq(k).find('#'+id+'_grad'+(k+1)).find("stop").eq(0).attr({
                            'offset': 100 - (rating * 10 % 10) * 10 + '%'
                        });
                        shapes.eq(k).find('#'+id+'_grad'+(k+1)).find("stop").eq(0).attr({
                            'stop-color': settings.backgroundColor
                        });
                        shapes.eq(k).find('#'+id+'_grad'+(k+1)).find("stop").eq(1).attr({
                            'offset': 100 - (rating * 10 % 10) * 10 + '%'
                        });
                        shapes.eq(k).find('#'+id+'_grad'+(k+1)).find("stop").eq(1).attr({
                            'stop-color': fillColor
                        });
                    }
                    if (isDefined(endColorCoords)) {
                        fillColor = formulateNewColor(settings.count - 1, i);
                    }
                }
            } else {
                for (var i = 0; i < rating; i++) {
                    shapes.eq(i).find('#'+id+'_grad'+(i+1)).find("stop").eq(0).attr({
                        'offset': '100%'
                    });
                    shapes.eq(i).find('#'+id+'_grad'+(i+1)).find("stop").eq(0).attr({
                        'stop-color': fillColor
                    });
                    if (rating * 10 % 10 > 0) {
                        shapes.eq(Math.ceil(rating) - 1).find('#'+id+'_grad'+(i+1)).find("stop").eq(0).attr({
                            'offset': (rating * 10 % 10) * 10 + '%'
                        });
                        shapes.eq(Math.ceil(rating) - 1).find('#'+id+'_grad'+(i+1)).find("stop").eq(0).attr({
                            'stop-color': fillColor
                        });
                    }
                    if (isDefined(endColorCoords)) {
                        fillColor = formulateNewColor(settings.count, i);
                    }
                }
            }
        }

        var formulateNewColor = function(totalCount, currentVal) {
            var avgFill = [];
            for (var i = 0; i < 3; i++) {
                var diff = Math.round((startColorCoords[i] - endColorCoords[i]) / totalCount);
                var newValue = startColorCoords[i] + (diff * (currentVal + 1));
                if (newValue / 256)
                    avgFill[i] = (startColorCoords[i] - (diff * (currentVal + 1))) % 256;
                else
                    avgFill[i] = (startColorCoords[i] + (diff * (currentVal + 1))) % 256;
            }
            return "rgba(" + avgFill[0] + "," + avgFill[1] + "," + avgFill[2] + "," + settings.opacity + ")";
        };



        function colorToRGBA(color) {
            var cvs, ctx;
            cvs = document.createElement('canvas');
            cvs.height = 1;
            cvs.width = 1;
            ctx = cvs.getContext('2d');
            ctx.fillStyle = color;
            ctx.fillRect(0, 0, 1, 1);
            return ctx.getImageData(0, 0, 1, 1).data;
        }

        function onMouseLeave() {
            if (!settings.readOnly) {
                showRating(settings.rating);
				onChange(null, {rating : settings.rating});
            }
        }

		function workOutPrecision(num) {
			var multiplactiveInverse = 1/settings.precision;

			return Math.ceil(num*multiplactiveInverse)/multiplactiveInverse;
		}

        function onEnterOrClickEvent(e, ith, label, update) {
            if (settings.readOnly) return;

            var svg = shapes.eq(ith - 1);
            var partial;

            if (settings.horizontal) {
                partial = (e.pageX - svg.offset().left) / svg.width();
            } else {
                partial = (e.pageY - svg.offset().top) / svg.height();
            }

            var count = (settings.max - settings.min) / settings.count;
            partial = (settings.reverse) ? partial : 1 - partial;
            var rating = ((settings.reverse ? (settings.max - settings.min - ith + 1) : ith) - partial) * count;
            rating = settings.min + Number(workOutPrecision(rating));
			
			if (rating < settings.minSelected) rating = settings.minSelected;
            if (rating <= settings.max && rating >= settings.min) {
                showRating(rating);
                if (update) settings.rating = rating;
                svg.trigger(label, {
                    rating: rating
                });
            }
        }

        function onTouchOrTapEvent(e, ith, label, update) {
            if (settings.readOnly) return;

			var touches = e.originalEvent.changedTouches;
			// Ignore multi-touch
			if (touches.length > 1) return;
			var touch = touches[0];
				
			var svg = shapes.eq(ith - 1);
			var partial;
			if (settings.horizontal) {
				partial = (touch.pageX - svg.offset().left) / svg.width();
			} else {
				partial = (touch.pageY - svg.offset().top) / svg.height();
			}
				
			var count = (settings.max - settings.min) / settings.count;
			partial = (settings.reverse) ? partial : 1 - partial;
			var rating = ((settings.reverse ? (settings.max - settings.min - ith + 1) : ith) - partial) * count;
			rating = settings.min + Number(workOutPrecision(rating));
			
			if (rating < settings.minSelected) rating = settings.minSelected;
			if (rating <= settings.max && rating >= settings.min) {
                showRating(rating);
                if (update) settings.rating = rating;
                svg.trigger(label, {
                    rating: rating
                });
            }
        }
		
        function onMouseEnter(i) {
            return function(e) {
                onEnterOrClickEvent(e, i, "JRate.change");
            };
        }

        function onMouseClick(i) {
            return function(e) {
                onEnterOrClickEvent(e, i, "JRate.set", true);
            };
        }

		function onTouchEnter(i) {
			return function(e) {
                onTouchOrTapEvent(e, i, "JRate.touch");
            };
		}
		
		function onTouchClick(i) {
			return function(e) {
                onTouchOrTapEvent(e, i, "JRate.tap", true);
            };
		}
		
        function onChange(e, data) {
            if (settings.onChange && typeof settings.onChange === "function") {
                settings.onChange.apply(this, [data.rating]);
            }
        }

        function onSet(e, data) {
            if (settings.onSet && typeof settings.onSet === "function") {
                settings.onSet.apply(this, [data.rating]);
            }
        }

        function drawShape() {
            var svg, i, sw, sh;
            for (i = 0; i < settings.count; i++) {
                $jRate.append(getShape(i+1));
            }
            shapes = $jRate.find('svg');
            for (i = 0; i < settings.count; i++) {
                svg = shapes.eq(i);
                bindEvents(svg, i + 1);
                if (!settings.horizontal) {
                    svg.css({
                        'display': 'block',
                        'margin-bottom': settings.shapeGap || '0px'
                    });
                } else {
                    svg.css('margin-right', (settings.shapeGap || '0px'));
                }
                if (settings.widthGrowth) {
                    sw = 'scaleX(' + (1 + settings.widthGrowth * i) + ')';
                    svg.css({
                        'transform': sw,
                        '-webkit-transform': sw,
                        '-moz-transform': sw,
                        '-ms-transform': sw,
                        '-o-transform': sw,
                    });
                }

                if (settings.heightGrowth) {
                    sh = 'scaleY(' + (1 + settings.heightGrowth * i) + ')';
                    svg.css({
                        'transform': sh,
                        '-webkit-transform': sh,
                        '-moz-transform': sh,
                        '-ms-transform': sh,
                        '-o-transform': sh,
                    });
                }
            }
            showNormalRating();
            showRating(settings.rating);
            shapes.attr({
                width: settings.width,
                height: settings.height
            });
        }

        //TODO
        //Validation implementation
        //Mini to max size

        //TODO Add this as a part of validation
        if (settings.startColor) startColorCoords = colorToRGBA(settings.startColor);
        if (settings.endColor) endColorCoords = colorToRGBA(settings.endColor);

        setCSS();
		drawShape();;

        return $.extend({}, this, {
            "getRating": getRating,
            "setRating": setRating,
            "setReadOnly": function(flag) {
                settings.readOnly = flag;
            },
            "isReadOnly": function() {
                return settings.readOnly;
            },
        });
    };
}(jQuery));
