<?php if (!defined('__TYPECHO_ROOT_DIR__'))
	exit; ?>
<style>
	/* 全屏遮罩，默认隐藏 */
	#r18-confirm-overlay {
		display: none;
		position: fixed;
		inset: 0;
		background: var(--bg-color);
		z-index: 2147483647;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	#r18-confirm-box {
		background: var(--card-color);
		color: var(--text-primary);
		min-width: 320px;
		max-width: 90%;
		border-radius: 12px;
		padding: 24px;
		box-shadow: 0 12px 30px rgba(0, 0, 0, 0.5);
		text-align: center;
	}

	#r18-confirm-box .row {
		margin: 12px 0;
	}

	.icon {
		font-size: 32px;
		margin-right: 8px;
		vertical-align: middle;
	}

	.warn-text {
		font-size: 28px;
		font-weight: 600;
		vertical-align: middle;
	}

	.buttons {
		display: flex;
		gap: 12px;
		justify-content: center;
		margin-top: 8px;
	}

	.buttons button {
		padding: 10px 18px;
		border-radius: 8px;
		border: 0;
		cursor: var(--cursor-pointer);
		font-size: 16px;
	}

	.confirm-btn {
		background: #498500;
		color: var(--text-primary);
	}

	.reject-btn {
		background: #ed5f54;
		color: var(--text-primary);
	}
</style>

<div id="r18-confirm-overlay" role="dialog" aria-modal="true" aria-labelledby="r18-title" aria-describedby="r18-desc">
	<div id="r18-confirm-box">
		<div class="row" id="r18-title">
			<span class="icon" aria-hidden="true">⚠️</span>
			<span class="warn-text">警告：准入禁止</span>、
		</div>
		<div class="row" id="r18-desc">请确认你已年满18周岁，且理解该内容可能会引发不适。请遵守当地法律法规。</div>
		<div class="row buttons">
			<button class="confirm-btn" id="r18-confirm-yes">我已经年满18周岁</button>
			<button class="reject-btn" id="r18-confirm-no">我未满18周岁</button>
		</div>
	</div>
</div>

<script>
	(function () {
		var key = 'isConfirmedR18Page';
		var overlay = document.getElementById('r18-confirm-overlay');
		var yes = document.getElementById('r18-confirm-yes');
		var no = document.getElementById('r18-confirm-no');

		try {
			var confirmed = localStorage.getItem(key);
			if (confirmed === 'true') {
				overlay.style.display = 'none';
				return;
			}
		} catch (e) {
		}

		overlay.style.display = 'flex';
		try { yes.focus(); } catch (e) { }

		yes.addEventListener('click', function () {
			try { localStorage.setItem(key, 'true'); } catch (e) { }
			overlay.style.display = 'none';
		});

		no.addEventListener('click', function () {
			try {
				window.open('', '_self');
				window.close();
				setTimeout(function () {
					if (!document.hidden) { location.href = 'about:blank'; }
				}, 200);
			} catch (e) {
				location.href = 'about:blank';
			}
		});

		document.addEventListener('keydown', function (e) {
			if (overlay.style.display === 'flex') {
				if (e.key === 'Enter') {
					yes.click();
				} else if (e.key === 'Escape') {
					no.click();
				}
			}
		}, true);
	})();
</script>