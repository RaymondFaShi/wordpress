/* ==========================================================================
   common.js — 全ページ共通の挙動
   依存：jQuery 3.7 / Bootstrap 5.3（bundle）
   ========================================================================== */
(function ($) {
  'use strict';

  /* ------------------------------------------------------------------------
     1. ヘッダー吸着 ＋ トップへ戻る
     バナー（#heroSlider のあるセクション）を通過したら固定＋圧縮する
     ------------------------------------------------------------------------ */
  var StickyHeader = {
    init: function () {
      this.$header = $('#siteHeader');
      if (!this.$header.length) { return; }
      this.$spacer = $('#headerSpacer');
      this.$toTop = $('#toTop');
      this.$target = $('#heroSlider').closest('section');
      if (!this.$target.length) { this.$target = $('main section').first(); }
      this.naturalHeight = 0;
      this.measure();
      this.bind();
      this.update();
    },
    measure: function () {
      if (!this.$header.hasClass('is-stuck')) {
        this.naturalHeight = this.$header.outerHeight();
      }
    },
    isPast: function () {
      if (!this.$target.length) { return false; }
      return this.$target[0].getBoundingClientRect().bottom <= 0;
    },
    update: function () {
      var past = this.isPast();
      var stuck = this.$header.hasClass('is-stuck');

      this.$toTop.toggleClass('is-on', past);
      if (past === stuck) { return; } // 状態が同じなら何もしない（連続スクロールでも安全）

      if (past) {
        // 固定で要素が流れから外れる分をスペーサーで補い、ガタつきを防ぐ
        this.$spacer.css('height', this.naturalHeight + 'px');
        this.$header.addClass('is-stuck');
      } else {
        this.$header.removeClass('is-stuck');
        this.$spacer.css('height', '0px');
      }
    },
    bind: function () {
      var self = this;
      var ticking = false;
      $(window).on('scroll', function () {
        if (ticking) { return; }
        ticking = true;
        window.requestAnimationFrame(function () {
          self.update();
          ticking = false;
        });
      }).on('resize', function () {
        self.measure();
        self.update();
      });
    }
  };

  /* ------------------------------------------------------------------------
     2. 検索サジェスト
     ------------------------------------------------------------------------ */
  var SearchSuggest = {
    data: [
      { group: 'カテゴリー', tag: 'カテゴリー', items: ['使い捨てベイプ', 'リキッド', 'カートリッジ', 'スターターキット', 'シーシャ', '加熱式タバコ'] },
      { group: 'ブランド', tag: 'ブランド', items: ['ELFBAR', 'Bang King', 'Uwell', 'Vaporesso', 'Al Fakher', 'BubbleMon', 'Waspe', 'OKSO'] },
      { group: '商品', tag: '商品', items: ['ELFBAR BC5000 使い捨てベイプ 5000puff', 'Bang King 12000 使い捨てベイプ', 'Vaporesso XROS 4 スターターキット', 'Uwell Caliburn G3 スターターキット', 'Al Fakher シーシャフレーバー 50g'] }
    ],
    init: function () {
      var self = this;
      this.$area = $('#siteSearchArea');
      if (!this.$area.length) { return; }
      this.$input = $('#searchInput');
      this.$panel = $('#searchSuggest');
      this.activeIndex = -1;
      this.flat = [];
      this.timer = null;

      this.$input
        .on('focus', function () { self.open(); })
        .on('input', function () {
          window.clearTimeout(self.timer);
          self.timer = window.setTimeout(function () { self.open(); }, 200); // 200msデバウンス
        })
        .on('keydown', function (e) {
          if (e.key === 'ArrowDown') { e.preventDefault(); self.open(); self.move(1); }
          else if (e.key === 'ArrowUp') { e.preventDefault(); self.move(-1); }
          else if (e.key === 'Enter' && self.activeIndex > -1) { e.preventDefault(); self.$input.val(self.flat[self.activeIndex]); self.close(); }
          else if (e.key === 'Escape') { self.close(); }
        });

      this.$panel
        .on('mousedown', '.suggest__item', function (e) {
          e.preventDefault();
          self.$input.val(self.flat[Number($(this).data('index'))] || '');
          self.close();
        })
        .on('mouseenter', '.suggest__item', function () {
          self.$panel.find('.suggest__item').removeClass('is-active');
          $(this).addClass('is-active');
        });

      $(document).on('click', function (e) {
        if (!self.$area[0].contains(e.target)) { self.close(); }
      });
    },
    render: function (keyword) {
      var q = ($.trim(keyword) || '').toLowerCase();
      var html = '';
      this.flat = [];
      $.each(this.data, function (_, group) {
        var hit = $.grep(group.items, function (item) {
          return !q || item.toLowerCase().indexOf(q) > -1;
        }).slice(0, 6);
        if (!hit.length) { return; }
        html += '<div class="suggest__group"><p class="suggest__title">' + group.group + '</p>';
        $.each(hit, function (_, item) {
          var index = this.flat.push(item) - 1;
          html += '<div class="suggest__item" role="option" data-index="' + index + '">' +
            '<span class="suggest__tag">' + group.tag + '</span><span>' + item + '</span></div>';
        }.bind(this));
        html += '</div>';
      }.bind(this));
      this.$panel.html(html || '<p class="suggest__empty">該当する候補が見つかりませんでした。</p>');
      this.activeIndex = -1;
    },
    open: function () {
      this.render(this.$input.val());
      this.$panel.addClass('is-open');
      this.$input.attr('aria-expanded', 'true');
    },
    close: function () {
      this.$panel.removeClass('is-open');
      this.$input.attr('aria-expanded', 'false');
      this.activeIndex = -1;
    },
    move: function (dir) {
      var $items = this.$panel.find('.suggest__item');
      if (!$items.length) { return; }
      $items.removeClass('is-active');
      this.activeIndex += dir;
      if (this.activeIndex < 0) { this.activeIndex = $items.length - 1; }
      if (this.activeIndex >= $items.length) { this.activeIndex = 0; }
      $items.eq(this.activeIndex).addClass('is-active');
    }
  };

  /* ------------------------------------------------------------------------
     3. ホバーで開くドロップダウン（アカウント・カート）
     Bootstrap の Dropdown をそのまま使い、hover のみ補助する
     ------------------------------------------------------------------------ */
  var HoverDropdown = {
    init: function () {
      if (!window.matchMedia('(hover:hover)').matches || !window.bootstrap) { return; }
      $('.js-hover-dropdown').each(function () {
        var $wrap = $(this);
        var el = this.querySelector('[data-bs-toggle="dropdown"]');
        var menu = this.querySelector('.dropdown-menu');
        if (!el) { return; }
        var instance = window.bootstrap.Dropdown.getOrCreateInstance(el);
        var $toggle = $(el);
        var hideTimer = null;

        // 中央揃えのまま画面外へはみ出さないよう、必要分だけ左右に寄せる
        function clampPanel() {
          if (!menu) { return; }
          menu.style.marginLeft = '0px';
          var rect = menu.getBoundingClientRect();
          if (!rect.width) { return; }
          var edge = 8;
          var shift = 0;
          if (rect.right > window.innerWidth - edge) { shift = window.innerWidth - edge - rect.right; }
          if (rect.left + shift < edge) { shift = edge - rect.left; }
          menu.style.marginLeft = shift ? shift + 'px' : '0px';
        }

        $wrap.on('mouseenter', function () {
          window.clearTimeout(hideTimer);
          // Bootstrap は show() 内で toggle を focus() するため、ポインタ操作時は枠線を抑止する
          $toggle.addClass('is-hover-open');
          instance.show();
          clampPanel();
        }).on('mouseleave', function () {
          // ゆっくり動かしたときにパネルへ入る前で消えないよう、少し猶予を持たせる
          window.clearTimeout(hideTimer);
          hideTimer = window.setTimeout(function () {
            instance.hide();
            if (menu) { menu.style.marginLeft = ''; }
          }, 260);
        });
      });
      // キーボード操作に切り替わったら枠線を元に戻す
      $(document).on('keydown', function () {
        $('.is-hover-open').removeClass('is-hover-open');
      });
    }
  };

  /* ------------------------------------------------------------------------
     4. トースト（カート投入のフィードバック）
     ------------------------------------------------------------------------ */
  function showToast(message) {
    var $area = $('#toastArea');
    if (!$area.length || !window.bootstrap) { return; }
    var $toast = $(
      '<div class="toast site-toast" role="status" aria-live="polite" aria-atomic="true">' +
        '<div class="d-flex align-items-center">' +
          '<div class="toast-body"></div>' +
          '<button type="button" class="btn-close btn-close-white me-2 ms-2" data-bs-dismiss="toast" aria-label="閉じる"></button>' +
        '</div>' +
      '</div>'
    );
    $toast.find('.toast-body').text(message);
    $area.append($toast);
    $toast.on('hidden.bs.toast', function () { $toast.remove(); });
    window.bootstrap.Toast.getOrCreateInstance($toast[0], { delay: 2600 }).show();
  }

  /* ------------------------------------------------------------------------
     5. 年齢確認モーダル（20歳以上のみ）
     ・#ageGate を置いたページでのみ動く（マークアップを置くだけで有効）
     ・背景クリックと Esc では閉じない
     ・「いいえ」を選ぶと確認内容を案内に差し替え、以降の操作を止める
     ------------------------------------------------------------------------ */
  var AgeGate = {
    init: function () {
      var el = document.getElementById('ageGate');
      if (!el || !window.bootstrap) { return; }

      this.$root = $(el);
      this.$ask = $('#ageGateAsk');
      this.$deny = $('#ageGateDeny');
      this.modal = window.bootstrap.Modal.getOrCreateInstance(el, {
        backdrop: 'static',
        keyboard: false
      });

      this.bind();
      this.modal.show();
    },

    bind: function () {
      var self = this;
      $('#ageGateYes').on('click', function () {
        self.modal.hide();
      });
      $('#ageGateNo').on('click', function () {
        self.deny();
      });
    },

    deny: function () {
      this.$ask.prop('hidden', true);
      this.$deny.prop('hidden', false);
      this.$root.attr('aria-labelledby', 'ageGateDenyTitle').removeAttr('aria-describedby');
      this.$deny.trigger('focus');
    }
  };

  /* ------------------------------------------------------------------------
     6. タブリストの外にある「タブへ移動するリンク」の補助
     Bootstrap のタブはタブリスト内の要素でしか切り替わらないため、
     本文中のリンク（.js-goto-tab）は該当のタブボタンに処理を委ねる
     ------------------------------------------------------------------------ */
  var TabJump = {
    init: function () {
      $(document).on('click', '.js-goto-tab', function (e) {
        if (!window.bootstrap) { return; }
        var target = $(this).attr('data-bs-target') || $(this).attr('href');
        if (!target || target.charAt(0) !== '#') { return; }
        var toggle = document.querySelector('[role="tablist"] [data-bs-target="' + target + '"]');
        if (!toggle) { return; }
        e.preventDefault();
        window.bootstrap.Tab.getOrCreateInstance(toggle).show();
      });
    }
  };

  /* ------------------------------------------------------------------------
     7. ゲスト注文の確認入口（全ページ共通）
     会員アカウントを持たない購入者が、未払い注文を探して
     再決済ページへ戻れるようにする。
     ------------------------------------------------------------------------ */
  var GuestOrderEntry = {
    init: function () {
      var script = $('script[src$="assets/js/common.js"]').last().attr('src') || 'assets/js/common.js';
      var base = script.replace(/assets\/js\/common\.js(?:\?.*)?$/, '');
      var href = base + 'guest-order-lookup.html';
      var label = 'ゲスト注文・支払いを確認';

      $('.account-menu__panel').each(function () {
        var $panel = $(this);
        if ($panel.find('.account-menu__guest-order').length) { return; }
        var $item = $('<li class="account-menu__guest-order"><a class="dropdown-item" href="' +
          href + '">' + label + '</a></li>');
        var $logout = $panel.find('.account-menu__logout');
        if ($logout.length) { $item.insertBefore($logout); }
        else { $panel.append($item); }
      });

      $('#mobileNav .mobile-nav__list').last().each(function () {
        var $list = $(this);
        if ($list.find('.mobile-nav__guest-order').length) { return; }
        $list.append(
          '<li class="mobile-nav__guest-order"><a href="' + href + '">' +
            '<span>' + label + '</span><em>未払い注文</em></a></li>'
        );
      });

      $('.site-footer__list').first().each(function () {
        var $list = $(this);
        if ($list.find('.site-footer__guest-order').length) { return; }
        $list.append('<li class="site-footer__guest-order"><a href="' + href + '">ゲスト注文・支払いを確認</a></li>');
      });
    }
  };

  /* ------------------------------------------------------------------------
     8. トップへ戻る
     ------------------------------------------------------------------------ */
  var ToTop = {
    init: function () {
      var $btn = $('#toTop');
      if (!$btn.length) { return; }
      $btn.on('click', function () {
        window.scrollTo(0, 0);
      });
    }
  };

  /* ------------------------------------------------------------------------
     9. イベント委譲（お気に入り・カート投入）
     ------------------------------------------------------------------------ */
  var DelegatedActions = {
    init: function () {
      // お気に入り
      $(document).on('click', '.js-fav', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var on = $btn.toggleClass('is-on').hasClass('is-on');
        $btn.attr('aria-pressed', on ? 'true' : 'false')
            .attr('aria-label', on ? 'お気に入りから削除' : 'お気に入りに追加');
      });

      // カートに入れる（デモ：トーストを表示するのみ）
      // ページ側でカートドロワーを実装している場合（window.vapekumo.cartDrawer）はそちらに任せる
      $(document).on('click', '.js-add-cart', function (e) {
        if (window.vapekumo && window.vapekumo.cartDrawer) { return; }
        e.preventDefault();
        var name = $(this).data('product-name') || '商品';
        showToast(name + ' をカートに追加しました。');
      });
    }
  };

  /* ------------------------------------------------------------------------
     10. 他ページへ公開する最小限のAPI（名前空間は1つに抑える）
     ------------------------------------------------------------------------ */
  window.vapekumo = window.vapekumo || {};
  window.vapekumo.toast = showToast;

  /* ------------------------------------------------------------------------
     起動
     ------------------------------------------------------------------------ */
  $(function () {
    StickyHeader.init();
    SearchSuggest.init();
    HoverDropdown.init();
    AgeGate.init();
    TabJump.init();
    GuestOrderEntry.init();
    ToTop.init();
    DelegatedActions.init();
  });
}(jQuery));
