(function () {
	'use strict';

	var config = window.noravoConfig || {};
	var root = document.getElementById('noravo-root');

	if (!root || !config.restUrl || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
		root && root.classList.add('noravo-reduced-motion');
	}

	if (!root || !config.restUrl) {
		return;
	}

	var queue = [];
	var shown = 0;
	var active = null;
	var max = Number(config.maxPerPage || 5);
	var interval = Number(config.interval || 9000);
	var i18n = config.i18n || {};

	function text(value) {
		return typeof value === 'string' ? value : '';
	}

	function icon(name) {
		var icons = {
			bag: 'M7 8V6a5 5 0 0 1 10 0v2h2.2a1 1 0 0 1 1 .9l.8 11a2 2 0 0 1-2 2.1H5a2 2 0 0 1-2-2.1l.8-11a1 1 0 0 1 1-.9H7Zm2 0h6V6a3 3 0 0 0-6 0v2Z',
			spark: 'M12 2l1.7 6.3L20 10l-6.3 1.7L12 18l-1.7-6.3L4 10l6.3-1.7L12 2Zm-6 13 1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3Z',
			star: 'm12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8-6.2-3.2L5.8 21 7 14.2 2 9.3l6.9-1L12 2Z',
			dot: 'M12 22a10 10 0 1 1 0-20 10 10 0 0 1 0 20Zm0-6a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z'
		};

		return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="' + (icons[name] || icons.dot) + '"></path></svg>';
	}

	function timeAgo(timestamp) {
		var diff = Math.max(1, Math.round((Date.now() / 1000 - Number(timestamp || 0)) / 60));

		if (diff < 2) {
			return i18n.justNow || 'Just now';
		}

		if (diff < 60) {
			return format(i18n.minutesAgo || '%d minutes ago', diff);
		}

		var hours = Math.round(diff / 60);

		if (hours < 2) {
			return i18n.hourAgo || '1 hour ago';
		}

		return format(i18n.hoursAgo || '%d hours ago', hours);
	}

	function format(pattern, value) {
		return pattern.replace('%d', value);
	}

	function build(notification) {
		var item = document.createElement(notification.cta_url ? 'a' : 'div');
		item.className = 'noravo-toast';

		if (notification.cta_url) {
			item.href = notification.cta_url;
		}

		item.innerHTML = [
			media(notification),
			'<span class="noravo-toast-body">',
			'<strong>' + escapeHtml(text(notification.title)) + '</strong>',
			'<span>' + escapeHtml(text(notification.message)) + '</span>',
			'<small>' + escapeHtml(timeAgo(notification.timestamp)) + '</small>',
			'</span>'
		].join('');

		return item;
	}

	function media(notification) {
		if (notification.image) {
			return '<span class="noravo-toast-image"><img src="' + escapeHtml(encodeURI(text(notification.image))) + '" alt=""></span>';
		}

		return '<span class="noravo-toast-icon">' + icon(text(notification.icon)) + '</span>';
	}

	function escapeHtml(value) {
		return value.replace(/[&<>"']/g, function (char) {
			return {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			}[char];
		});
	}

	function showNext() {
		if (active || !queue.length || shown >= max) {
			return;
		}

		active = build(queue.shift());
		root.appendChild(active);
		requestAnimationFrame(function () {
			active.classList.add('is-visible');
		});

		shown += 1;

		setTimeout(function () {
			if (!active) {
				return;
			}

			active.classList.remove('is-visible');

			setTimeout(function () {
				active && active.remove();
				active = null;
				showNext();
			}, 260);
		}, Math.max(4000, interval - 1000));
	}

	function start(notifications) {
		queue = Array.isArray(notifications) ? notifications.slice(0, max) : [];
		setTimeout(function () {
			showNext();
			setInterval(showNext, interval);
		}, Number(config.initialDelay || 2500));
	}

	fetch(config.restUrl, { credentials: 'same-origin' })
		.then(function (response) {
			return response.ok ? response.json() : { notifications: [] };
		})
		.then(function (payload) {
			start(payload.notifications || []);
		})
		.catch(function () {
			start([]);
		});
}());
