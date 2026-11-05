/* global jQuery, vapekumoSettings, bootstrap */
(function ($) {
  'use strict';

  var settings = window.vapekumoSettings || {};
  var storageKey = 'vapekumo_favorites';

  function getGuestFavorites() {
    try {
      return JSON.parse(window.localStorage.getItem(storageKey) || '[]').map(Number);
    } catch (error) {
      return [];
    }
  }

  function setGuestFavorites(ids) {
    window.localStorage.setItem(storageKey, JSON.stringify(Array.from(new Set(ids.map(Number)))));
  }

  function updateFavoriteCount(count) {
    count = Number(count || 0);
    $('[data-fav-count]').text(count);
    $('#favToggle').attr('aria-label', 'お気に入り（' + count + '点）');
  }

  function showToast(message) {
    var area = document.getElementById('toastArea');
    if (!area || !window.bootstrap) {
      return;
    }
    var toast = document.createElement('div');
    toast.className = 'toast site-toast';
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    toast.innerHTML = '<div class="d-flex align-items-center"><div class="toast-body"></div><button type="button" class="btn-close btn-close-white me-2 ms-2" data-bs-dismiss="toast" aria-label="閉じる"></button></div>';
    toast.querySelector('.toast-body').textContent = message;
    area.appendChild(toast);
    toast.addEventListener('hidden.bs.toast', function () {
      toast.remove();
    });
    window.bootstrap.Toast.getOrCreateInstance(toast, { delay: 2800 }).show();
  }

  var StickyHeader = {
    init: function () {
      this.$header = $('#siteHeader');
      this.$spacer = $('#headerSpacer');
      this.$toTop = $('#toTop');
      if (!this.$header.length) {
        return;
      }
      this.$target = $('#heroSlider').closest('section');
      if (!this.$target.length) {
        this.$target = $('main section').first();
      }
      this.naturalHeight = 0;
      this.measure();
      this.bind();
    },
    measure: function () {
      if (!this.$header.hasClass('is-stuck')) {
        this.naturalHeight = this.$header.outerHeight();
      }
    },
    update: function () {
      var isCheckout = document.body.classList.contains('woocommerce-checkout');
      var stuck = this.$header.hasClass('is-stuck');
      if (isCheckout) {
        this.$toTop.toggleClass('is-on', false);
        if (stuck) {
          this.$header.removeClass('is-stuck');
          this.$spacer.css('height', '0px');
        }
        return;
      }
      var past = this.$target.length > 0 && this.$target[0].getBoundingClientRect().bottom <= 0;
      this.$toTop.toggleClass('is-on', past);
      if (past === stuck) {
        return;
      }
      if (past) {
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
        if (ticking) {
          return;
        }
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

  var HoverDropdown = {
    init: function () {
      if (!window.matchMedia('(hover:hover)').matches || !window.bootstrap) {
        return;
      }
      $('.js-hover-dropdown').each(function () {
        var el = this.querySelector('[data-bs-toggle="dropdown"]');
        if (!el) {
          return;
        }
        var instance = window.bootstrap.Dropdown.getOrCreateInstance(el);
        var hideTimer = null;
        $(this).on('mouseenter', function () {
          window.clearTimeout(hideTimer);
          instance.show();
        }).on('mouseleave', function () {
          window.clearTimeout(hideTimer);
          hideTimer = window.setTimeout(function () {
            instance.hide();
          }, 220);
        });
      });
    }
  };

  var SearchSuggest = {
    init: function () {
      this.$area = $('#siteSearchArea');
      if (!this.$area.length) {
        return;
      }
      this.$input = $('#searchInput');
      this.$panel = $('#searchSuggest');
      this.timer = null;
      this.controller = null;
      this.bind();
    },
    bind: function () {
      var self = this;
      this.$input.on('input', function () {
        window.clearTimeout(self.timer);
        var term = $.trim(self.$input.val());
        if (!term) {
          self.close();
          return;
        }
        self.timer = window.setTimeout(function () {
          self.fetch(term);
        }, 220);
      }).on('keydown', function (event) {
        if (event.key === 'Escape') {
          self.close();
        }
      });
      $(document).on('click', function (event) {
        if (!self.$area[0].contains(event.target)) {
          self.close();
        }
      });
    },
    fetch: function (term) {
      var self = this;
      if (this.controller) {
        this.controller.abort();
      }
      this.controller = new AbortController();
      this.$panel.addClass('is-open').html('<p class="suggest__empty">検索しています…</p>');
      this.$input.attr('aria-expanded', 'true');
      window.fetch(settings.restUrl + 'search-suggest?term=' + encodeURIComponent(term), {
        signal: this.controller.signal,
        credentials: 'same-origin'
      }).then(function (response) {
        if (!response.ok) {
          throw new Error('search request failed');
        }
        return response.json();
      }).then(function (data) {
        self.render(data, term);
      }).catch(function (error) {
        if (error.name !== 'AbortError') {
          self.$panel.html('<p class="suggest__empty">候補を取得できませんでした。Enterで検索できます。</p>');
        }
      });
    },
    render: function (data, term) {
      var html = '';
      (data.categories || []).forEach(function (item) {
        html += '<div class="suggest__group"><p class="suggest__title">カテゴリー</p>';
        html += '<a class="suggest__item" role="option" href="' + item.url + '"><span class="suggest__tag">カテゴリー</span><span>' + $('<span>').text(item.label).html() + '</span></a></div>';
      });
      if (data.products && data.products.length) {
        html += '<div class="suggest__group"><p class="suggest__title">商品</p>';
        data.products.forEach(function (item) {
          html += '<a class="suggest__item" role="option" href="' + item.url + '"><span class="suggest__tag">商品</span><span>' + $('<span>').text(item.label).html() + '</span><b>' + $('<span>').text(item.price).html() + '</b></a>';
        });
        html += '</div>';
      }
      if (!html) {
        html = '<p class="suggest__empty">「' + $('<span>').text(term).html() + '」に一致する候補がありません。Enterで検索できます。</p>';
      }
      html += '<a class="suggest__all" href="' + settings.shopUrl + '?s=' + encodeURIComponent(term) + '&post_type=product">「' + $('<span>').text(term).html() + '」の検索結果をすべて見る</a>';
      this.$panel.html(html);
    },
    close: function () {
      this.$panel.removeClass('is-open');
      this.$input.attr('aria-expanded', 'false');
    }
  };

  var Sliders = {
    init: function () {
      if ($.fn.slick) {
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        $('#heroSlider, #catBannerSlider').not('.slick-initialized').slick({
          arrows: true,
          dots: true,
          infinite: true,
          speed: reduce ? 0 : 500,
          autoplay: !reduce,
          autoplaySpeed: 5000,
          pauseOnHover: true,
          pauseOnFocus: true,
          swipeToSlide: true,
          accessibility: true
        });
      }
      $('[data-rail-viewport]').each(function () {
        var viewport = this;
        var wrap = viewport.closest('.rail__wrap');
        var prev = wrap.querySelector('.rail__nav--prev');
        var next = wrap.querySelector('.rail__nav--next');
        if (!prev || !next) {
          return;
        }
        function step() {
          return Math.max(viewport.clientWidth * 0.8, 200);
        }
        function sync() {
          var max = viewport.scrollWidth - viewport.clientWidth;
          prev.disabled = viewport.scrollLeft <= 4;
          next.disabled = viewport.scrollLeft >= max - 4;
        }
        prev.addEventListener('click', function () {
          viewport.scrollBy({ left: -step(), behavior: 'smooth' });
        });
        next.addEventListener('click', function () {
          viewport.scrollBy({ left: step(), behavior: 'smooth' });
        });
        viewport.addEventListener('scroll', sync);
        window.addEventListener('resize', sync);
        sync();
      });
    }
  };

  var Favorites = {
    init: function () {
      var self = this;
      var guestFavorites = getGuestFavorites();
      if (!settings.isLoggedIn) {
        $('.js-fav[data-product-id]').each(function () {
          var id = Number(this.getAttribute('data-product-id'));
          if (guestFavorites.includes(id)) {
            $(this).addClass('is-on').attr('aria-pressed', 'true').find('i').removeClass('bi-heart').addClass('bi-heart-fill');
          }
        });
      }
      $(document).on('click', '.js-fav[data-product-id]', function (event) {
        event.preventDefault();
        self.toggle($(this));
      });
      if (document.getElementById('vkFavorites') && !document.querySelector('#vkFavorites .u-fav, #vkFavorites .u-empty')) {
        this.renderFavorites();
      }
      this.refreshHeader();
    },
    toggle: function ($button) {
      var self = this;
      var id = Number($button.attr('data-product-id'));
      var active = !$button.hasClass('is-on');
      $button.toggleClass('is-on', active).attr('aria-pressed', active ? 'true' : 'false')
        .attr('aria-label', active ? 'お気に入りから削除' : 'お気に入りに追加')
        .find('i').toggleClass('bi-heart', !active).toggleClass('bi-heart-fill', active);

      if (!settings.isLoggedIn) {
        var ids = getGuestFavorites();
        ids = active ? ids.concat(id) : ids.filter(function (item) { return item !== id; });
        setGuestFavorites(ids);
        updateFavoriteCount(ids.length);
        this.refreshHeader();
        if (!active && $button.closest('#vkFavorites').length) {
          $button.closest('.u-fav').remove();
          if (!$('#vkFavorites .u-fav').length) {
            $('#favEmpty').removeAttr('hidden');
          }
        }
        return;
      }

      window.fetch(settings.restUrl + 'favorite', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': settings.nonce },
        body: JSON.stringify({ product_id: id })
      }).then(function (response) {
        return response.json();
      }).then(function (data) {
        if (typeof data.active !== 'boolean') {
          throw new Error('favorite failed');
        }
        updateFavoriteCount((data.favorites || []).length);
        self.refreshHeader();
        if (!active && $button.closest('#vkFavorites').length) {
          $button.closest('.u-fav').remove();
          if (!$('#vkFavorites .u-fav').length) {
            $('#favEmpty').removeAttr('hidden');
          }
        }
      }).catch(function () {
        $button.toggleClass('is-on', !active).attr('aria-pressed', !active ? 'true' : 'false');
        showToast('お気に入りを更新できませんでした。');
      });
    },
    refreshHeader: function () {
      var self = this;
      var ids = getGuestFavorites();
      var endpoint = settings.restUrl + 'favorites';
      if (!settings.isLoggedIn) {
        endpoint += '?' + (ids.length ? 'ids=' + encodeURIComponent(ids.join(',')) : 'ids=');
      }
      return window.fetch(endpoint, {
        credentials: 'same-origin',
        headers: { 'X-WP-Nonce': settings.nonce }
      }).then(function (response) {
        if (!response.ok) {
          throw new Error('favorite list failed');
        }
        return response.json();
      }).then(function (data) {
        self.renderHeader(data.products || [], data.favorites || []);
      }).catch(function () {
        if (!settings.isLoggedIn) {
          self.renderHeader([], []);
        }
      });
    },
    renderHeader: function (products, validIds) {
      if (!settings.isLoggedIn) {
        setGuestFavorites(validIds);
      }
      updateFavoriteCount(products.length);
      var $list = $('[data-fav-header-list]').first().empty();
      products.slice(0, 3).forEach(function (product) {
        var $row = $('<a>', {
          class: 'fav-menu__row',
          href: product.url
        });
        if (product.miniImage) {
          $row.append(product.miniImage);
        }
        var $body = $('<div>');
        $('<p>', { class: 'fav-menu__name' }).text(product.name || '').appendTo($body);
        $('<p>', { class: 'fav-menu__price' }).html(product.priceHtml || '').appendTo($body);
        $row.append($body);
        $list.append($row);
      });
      $('[data-fav-header-empty]').prop('hidden', products.length > 0);
    },
    renderFavorites: function () {
      var container = document.getElementById('vkFavorites');
      var self = this;
      if (!container) {
        return;
      }
      var ids = getGuestFavorites();
      var query = ids.length ? 'ids=' + ids.join(',') : 'ids=';
      window.fetch(settings.restUrl + 'favorites?' + query, {
        credentials: 'same-origin',
        headers: { 'X-WP-Nonce': settings.nonce }
      })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          self.renderHeader(data.products || [], data.favorites || []);
          if (!data.products || !data.products.length) {
            container.innerHTML = '<div class="cart-empty"><p class="cart-empty__title">お気に入りはまだありません</p><p class="cart-empty__text">商品カードのハートを押すと、ここに保存されます。</p><a class="btn btn-cta" href="' + settings.shopUrl + '">商品を探す</a></div>';
            return;
          }
          var html = '<div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-page">';
          data.products.forEach(function (product) {
            html += '<div class="col"><article class="product-card"><a class="product-card__media" href="' + product.url + '">' + product.image + '<button class="product-card__fav is-on js-fav" type="button" data-product-id="' + product.id + '" aria-label="お気に入りから削除" aria-pressed="true"><i class="bi bi-heart-fill" aria-hidden="true"></i></button></a><div class="product-card__body"><p class="product-card__brand">' + $('<span>').text(product.brand).html() + '</p><h2 class="product-card__name"><a href="' + product.url + '">' + $('<span>').text(product.name).html() + '</a></h2><div class="product-card__price">' + product.priceHtml + '</div><a class="btn btn-line product-card__btn" href="' + product.url + '">商品を見る</a></div></article></div>';
          });
          html += '</div>';
          container.innerHTML = html;
        });
    }
  };

  var Newsletter = {
    init: function () {
      $('[data-newsletter-form]').on('submit', function (event) {
        event.preventDefault();
        var form = this;
        var message = form.parentElement.querySelector('[data-newsletter-message]');
        var email = form.querySelector('input[type="email"]').value;
        window.fetch(settings.restUrl + 'newsletter', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email: email })
        }).then(function (response) {
          return response.json().then(function (data) {
            return { ok: response.ok, data: data };
          });
        }).then(function (result) {
          message.textContent = result.ok ? settings.newsletterText : (result.data.message || '登録できませんでした。');
          if (result.ok) {
            form.reset();
          }
        }).catch(function () {
          message.textContent = '通信に失敗しました。時間をおいて再度お試しください。';
        });
      });
    }
  };

  var BrandFilter = {
    init: function () {
      var input = document.getElementById('brandSearch');
      var list = document.getElementById('brandList');
      if (!input || !list) {
        return;
      }
      this.input = input;
      this.list = list;
      this.items = Array.prototype.slice.call(list.querySelectorAll('.brand-list__item'));
      this.initial = 'all';
      this.clear = document.getElementById('brandClear');
      this.count = document.getElementById('brandCount');
      this.empty = document.getElementById('brandEmpty');
      var self = this;
      input.addEventListener('input', function () { self.apply(); });
      $('#brandAz').on('click', '.brand-az__btn', function () {
        self.initial = this.getAttribute('data-initial');
        $('#brandAz .brand-az__btn').removeClass('is-active').attr('aria-pressed', 'false');
        $(this).addClass('is-active').attr('aria-pressed', 'true');
        self.apply();
      });
      if (this.clear) {
        this.clear.addEventListener('click', function () { self.reset(); });
      }
      $('#brandReset').on('click', function () { self.reset(); });
    },
    apply: function () {
      var query = this.input.value.trim().toLowerCase();
      var initial = this.initial;
      var shown = 0;
      this.items.forEach(function (item) {
        var visible = (initial === 'all' || item.getAttribute('data-initial') === initial) && (!query || item.getAttribute('data-name').includes(query));
        item.hidden = !visible;
        if (visible) {
          shown += 1;
        }
      });
      this.count.innerHTML = '該当 <b>' + shown + '</b> ブランド';
      this.empty.hidden = shown !== 0;
      this.clear.hidden = !query;
    },
    reset: function () {
      this.input.value = '';
      this.initial = 'all';
      $('#brandAz .brand-az__btn').removeClass('is-active').attr('aria-pressed', 'false');
      $('#brandAz .brand-az__btn[data-initial="all"]').addClass('is-active').attr('aria-pressed', 'true');
      this.apply();
      this.input.focus();
    }
  };

  var ContactForm = {
    init: function () {
      $('[data-contact-form]').on('submit', function (event) {
        event.preventDefault();
        var form = this;
        var result = document.getElementById('contactResult');
        var payload = {};
        new FormData(form).forEach(function (value, key) {
          payload[key] = value;
        });
        result.hidden = false;
        result.className = 'contact-form__result';
        result.textContent = '送信しています…';
        window.fetch(form.action, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        }).then(function (response) {
          return response.json().then(function (data) {
            return { ok: response.ok, data: data };
          });
        }).then(function (response) {
          if (!response.ok) {
            throw new Error(response.data.message || '送信できませんでした。');
          }
          result.classList.add('is-success');
          result.textContent = response.data.message;
          form.reset();
        }).catch(function (error) {
          result.classList.add('is-error');
          result.textContent = error.message || '送信できませんでした。';
        });
      });
    }
  };

  var AccountPreferences = {
    init: function () {
      $('[data-newsletter-preferences]').on('submit', function (event) {
        event.preventDefault();
        var form = this;
        var result = form.querySelector('[data-newsletter-preference-result]');
        var payload = {
          offers: form.querySelector('[name="offers"]').checked,
          new: form.querySelector('[name="new"]').checked,
          content: form.querySelector('[name="content"]').checked
        };
        result.textContent = '保存しています…';
        window.fetch(settings.restUrl + 'newsletter-preferences', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': settings.nonce },
          body: JSON.stringify(payload)
        }).then(function (response) {
          return response.json();
        }).then(function (data) {
          result.textContent = data.message || '保存しました。';
        }).catch(function () {
          result.textContent = '設定を保存できませんでした。';
        });
      });
      $('.js-pw-toggle').on('click', function () {
        var input = document.getElementById(this.getAttribute('data-target'));
        if (!input) {
          return;
        }
        var visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        this.setAttribute('aria-pressed', visible ? 'true' : 'false');
        $(this).find('i').toggleClass('bi-eye', !visible).toggleClass('bi-eye-slash', visible);
      });
      $('.woocommerce-form-register').on('submit', function (event) {
        var password = this.querySelector('[name="password"]');
        var confirmation = this.querySelector('[name="password_confirm"]');
        if (!password || !confirmation) {
          return;
        }
        confirmation.setCustomValidity(password.value === confirmation.value ? '' : '確認用パスワードが一致しません。');
        if (!this.checkValidity()) {
          event.preventDefault();
          confirmation.reportValidity();
        }
      }).on('input', '[name="password"], [name="password_confirm"]', function () {
        var form = this.form;
        form.querySelector('[name="password_confirm"]')?.setCustomValidity('');
      });
    }
  };

  var ProductDetail = {
    init: function () {
      this.gallery();
      this.variants();
      this.buyNow();
      $('.js-woo-review-tab').on('click', function (event) {
        event.preventDefault();
        var tab = document.querySelector('.reviews_tab a');
        if (tab) {
          tab.click();
          document.querySelector('.pdp-tabsec')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    },
    gallery: function () {
      var root = document.getElementById('pdpGallery');
      if (!root) {
        return;
      }
      var thumbs = root.querySelector('#pdpThumbs');
      var items = Array.prototype.slice.call(root.querySelectorAll('.pdp-gallery__thumb'));
      var image = root.querySelector('#pdpImage');
      var count = root.querySelector('#pdpCount');
      var up = root.querySelector('.js-pdp-up');
      var down = root.querySelector('.js-pdp-down');
      var stage = root.querySelector('.pdp-gallery__frame');
      var lens = root.querySelector('#pdpLens');
      var zoom = root.querySelector('#pdpZoom');
      var index = 0;
      if (!items.length || !image) {
        return;
      }
      function show(next) {
        index = Math.max(0, Math.min(items.length - 1, next));
        items.forEach(function (item) {
          var active = Number(item.getAttribute('data-index')) === index;
          item.classList.toggle('is-active', active);
          item.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        var activeItem = items[index];
        image.src = activeItem.getAttribute('data-src');
        image.alt = activeItem.getAttribute('data-alt') || '';
        if (zoom) {
          zoom.style.backgroundImage = 'url("' + image.src + '")';
        }
        if (count) {
          count.textContent = (index + 1) + ' / ' + items.length;
        }
        if (up) {
          up.disabled = index === 0;
        }
        if (down) {
          down.disabled = index === items.length - 1;
        }
        var listRect = thumbs.getBoundingClientRect();
        var itemRect = activeItem.getBoundingClientRect();
        if (itemRect.top < listRect.top || itemRect.bottom > listRect.bottom) {
          thumbs.scrollTop += (itemRect.top - listRect.top) - (listRect.height - itemRect.height) / 2;
        }
      }
      thumbs.addEventListener('click', function (event) {
        var button = event.target.closest('.pdp-gallery__thumb');
        if (button) {
          show(Number(button.getAttribute('data-index')));
        }
      });
      if (up) {
        up.addEventListener('click', function () { show(index - 1); });
      }
      if (down) {
        down.addEventListener('click', function () { show(index + 1); });
      }
      stage.addEventListener('mousemove', function (event) {
        if (!window.matchMedia('(min-width:992px) and (hover:hover)').matches || !lens || !zoom) {
          return;
        }
        var rect = stage.getBoundingClientRect();
        var lensW = lens.offsetWidth;
        var lensH = lens.offsetHeight;
        var panelW = zoom.offsetWidth;
        if (!lensW || !panelW) {
          return;
        }
        var left = Math.max(0, Math.min(rect.width - lensW, event.clientX - rect.left - lensW / 2));
        var top = Math.max(0, Math.min(rect.height - lensH, event.clientY - rect.top - lensH / 2));
        var factor = panelW / lensW;
        lens.style.left = left + 'px';
        lens.style.top = top + 'px';
        zoom.style.backgroundSize = (rect.width * factor) + 'px ' + (rect.height * factor) + 'px';
        zoom.style.backgroundPosition = (panelW / 2 - (left + lensW / 2) * factor) + 'px ' + (panelW / 2 - (top + lensH / 2) * factor) + 'px';
      });
      stage.addEventListener('mouseenter', function () {
        if (window.matchMedia('(min-width:992px) and (hover:hover)').matches) {
          root.classList.add('is-zooming');
        }
      });
      stage.addEventListener('mouseleave', function () {
        root.classList.remove('is-zooming');
      });
      show(0);
    },
    variants: function () {
      var container = document.getElementById('pdpVariants');
      var form = document.querySelector('.pdp-buy--woo form.cart');
      if (!container || !form) {
        return;
      }
      var selects = Array.prototype.slice.call(form.querySelectorAll('.variations select'));
      selects.forEach(function (select) {
        var options = Array.prototype.slice.call(select.options).filter(function (option) { return option.value; });
        var preferredOrder = ['メンソール', 'マンゴー', 'ブルーベリー', 'ピーチ', 'グレープ', 'ダブルアップル', 'レモンソーダ', 'ストロベリーアイス'];
        options.sort(function (a, b) {
          var ai = preferredOrder.indexOf(a.textContent.trim());
          var bi = preferredOrder.indexOf(b.textContent.trim());
          return (ai < 0 ? 999 : ai) - (bi < 0 ? 999 : bi);
        });
        if (!options.length) {
          return;
        }
        var label = select.closest('tr')?.querySelector('label');
        var labelText = label ? label.textContent.replace('必須', '').trim() : 'オプション';
        var group = document.createElement('div');
        group.className = 'pdp-variant';
        var head = document.createElement('p');
        head.className = 'pdp-variant__head';
        head.innerHTML = $('<span>').text(labelText).html() + '<span class="pdp-variant__value"></span>';
        var values = document.createElement('div');
        values.className = 'pdp-variant__options';
        values.setAttribute('role', 'group');
        values.setAttribute('aria-label', labelText + 'を選択');
        options.forEach(function (option) {
          var button = document.createElement('button');
          button.type = 'button';
          button.className = 'pdp-option';
          button.dataset.value = option.value;
          button.setAttribute('aria-pressed', 'false');
          button.textContent = option.textContent;
          button.addEventListener('click', function () {
            select.value = option.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            values.querySelectorAll('.pdp-option').forEach(function (item) {
              item.classList.toggle('is-active', item === button);
              item.setAttribute('aria-pressed', item === button ? 'true' : 'false');
            });
            head.querySelector('.pdp-variant__value').textContent = option.textContent;
          });
          values.appendChild(button);
        });
        var initial = options.find(function (option) { return option.value === select.value; }) || options[0];
        select.value = initial.value;
        select.dispatchEvent(new Event( 'change', { bubbles: true } ));
        values.querySelectorAll('.pdp-option').forEach(function (item) {
          var active = item.dataset.value === initial.value;
          item.classList.toggle('is-active', active);
          item.setAttribute('aria-pressed', active ? 'true' : 'false');
          if (active) {
            head.querySelector('.pdp-variant__value').textContent = item.textContent;
          }
        });
        group.appendChild(head);
        group.appendChild(values);
        container.appendChild(group);
      });
      form.querySelector('.variations')?.classList.add('pdp-variations-native');

    },
    buyNow: function () {
      var form = document.querySelector('.pdp-buy--woo form.cart');
      if (!form) {
        return;
      }
      var holder = form.querySelector('.woocommerce-variation-add-to-cart');
      if (!holder) {
        return;
      }
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'btn pdp-actions__buy js-buy-now';
      button.textContent = '今すぐ購入';
      holder.appendChild(button);
      button.addEventListener('click', function () {
        var empty = Array.prototype.some.call(form.querySelectorAll('.variations select'), function (select) { return !select.value; });
        if (empty) {
          showToast('商品オプションを選択してください。');
          return;
        }
        var productId = Number(form.getAttribute('data-product_id') || form.querySelector('[name="product_id"]')?.value || 0);
        var variationId = Number(form.querySelector('[name="variation_id"]')?.value || 0);
        var quantityInput = form.querySelector('input.qty');
        var quantity = Number(quantityInput ? quantityInput.value : 1);
        button.disabled = true;
        button.textContent = '処理中…';
        window.fetch(settings.restUrl + 'cart-add', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': settings.nonce },
          body: JSON.stringify({
            product_id: productId,
            variation_id: variationId,
            quantity: quantity,
            buy_now: true
          })
        }).then(function (response) {
          return response.json().then(function (data) {
            return { ok: response.ok, data: data };
          });
        }).then(function (result) {
          if (!result.ok) {
            throw new Error(result.data.message || '購入手続きを開始できませんでした。');
          }
          window.location.href = result.data.checkout_url || settings.checkoutUrl;
        }).catch(function (error) {
          button.disabled = false;
          button.textContent = '今すぐ購入';
          showToast(error.message || '購入手続きを開始できませんでした。');
        });
      });
    }
  };

  var CatalogCart = {
    init: function () {
      if (!$('.js-add-cart').length || !window.bootstrap) {
        return;
      }
      this.modalElement = document.getElementById('optionModal');
      this.drawerElement = document.getElementById('cartDrawer');
      this.modal = this.modalElement ? window.bootstrap.Modal.getOrCreateInstance(this.modalElement) : null;
      this.drawer = this.drawerElement ? window.bootstrap.Offcanvas.getOrCreateInstance(this.drawerElement) : null;
      this.pending = null;
      this.selectedVariation = null;
      var self = this;
      $(document).on('click', '.js-add-cart', function (event) {
        event.preventDefault();
        self.openProduct($(this));
      });
      $(document).on('change', '#optionList input[name="catalogVariation"]', function () {
        self.selectedVariation = Number(this.value);
        self.syncVariation();
      });
      $('#optionSubmit').on('click', function () {
        self.submitVariation();
      });
      if (this.modalElement) {
        this.modalElement.addEventListener('hidden.bs.modal', function () {
          if (self.queuedCart) {
            self.renderCart(self.queuedCart);
            self.queuedCart = null;
          }
        });
      }
    },
    data: function ($button) {
      var $card = $button.closest('.product-card');
      var raw = $card.attr('data-variations') || '[]';
      var variations = [];
      try {
        variations = JSON.parse(raw);
      } catch (error) {
        variations = [];
      }
      return {
        id: Number($card.attr('data-product-id')),
        type: $card.attr('data-product-type'),
        name: $card.attr('data-product-name'),
        image: $card.attr('data-product-image'),
        brand: $card.find('.product-card__brand').first().text().trim(),
        price: $card.find('.product-card__price').first().text().trim(),
        variations: variations
      };
    },
    openProduct: function ($button) {
      var product = this.data($button);
      this.pending = product;
      this.selectedVariation = null;
      if (product.type !== 'variable' || !product.variations.length) {
        this.addToCart(product.id, 0, $button);
        return;
      }
      var available = product.variations.filter(function (variation) { return variation.in_stock; });
      this.selectedVariation = available.length ? available[0].id : 0;
      $('#optionModalThumb').attr('src', product.image || '').attr('alt', product.name);
      $('#optionModalBrand').text(product.brand);
      $('#optionModalName').text(product.name);
      $('#optionModalPrice').text(product.price);
      $('#optionModalError').attr('hidden', true).text('');
      $('#optionList').html(product.variations.map(function (variation) {
        var stockText = variation.in_stock
          ? (variation.stock_qty !== null && variation.stock_qty <= 5 ? '残り' + variation.stock_qty + '点' : '在庫あり')
          : '在庫切れ';
        return '<li><label class="option-choice' + (variation.in_stock ? '' : ' is-disabled') + '">' +
          '<input class="option-choice__input" type="radio" name="catalogVariation" value="' + variation.id + '"' +
          (variation.id === this.selectedVariation ? ' checked' : '') +
          (variation.in_stock ? '' : ' disabled') + '>' +
          '<span class="option-choice__box"><span class="option-choice__name">' + this.escape(variation.label) + '</span><span class="option-choice__status">' + this.escape(stockText) + '</span></span>' +
          '</label></li>';
      }.bind(this)).join(''));
      this.syncVariation();
      this.modal.show();
    },
    syncVariation: function () {
      var variation = this.pending && this.pending.variations.find(function (item) {
        return item.id === this.selectedVariation;
      }, this);
      var $submit = $('#optionSubmit');
      if (!variation) {
        $submit.prop('disabled', true).text('選択してください');
        $('#optionStockNote').attr('class', 'option-stock-note is-out').text('選択できる規格がありません。');
        return;
      }
      $submit.prop('disabled', !variation.in_stock).text(variation.in_stock ? 'カートに入れる' : '在庫切れ');
      $('#optionStockNote')
        .attr('class', 'option-stock-note' + (!variation.in_stock ? ' is-out' : variation.stock_qty !== null && variation.stock_qty <= 5 ? ' is-low' : ''))
        .text(!variation.in_stock ? 'この規格は在庫切れです。' : variation.stock_qty !== null && variation.stock_qty <= 5 ? '在庫僅少：残り' + variation.stock_qty + '点です。' : '在庫あり・当日発送');
      if (variation.price) {
        $('#optionModalPrice').text(variation.price);
      }
    },
    submitVariation: function () {
      if (!this.pending || !this.selectedVariation) {
        return;
      }
      var variation = this.pending.variations.find(function (item) {
        return item.id === this.selectedVariation;
      }, this);
      if (!variation || !variation.in_stock) {
        this.syncVariation();
        return;
      }
      this.addToCart(this.pending.id, variation.id, $('#optionSubmit'), true);
    },
    addToCart: function (productId, variationId, $button, closeModal) {
      var self = this;
      $button.prop('disabled', true);
      window.fetch(settings.restUrl + 'cart-add', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': settings.nonce },
        body: JSON.stringify({ product_id: productId, variation_id: variationId, quantity: 1 })
      }).then(function (response) {
        return response.json().then(function (data) {
          return { ok: response.ok, data: data };
        });
      }).then(function (result) {
        if (!result.ok || !result.data.ok) {
          throw new Error(result.data.message || 'カートに追加できませんでした。');
        }
        if (closeModal && self.modal) {
          self.queuedCart = result.data;
          self.modal.hide();
        } else {
          self.renderCart(result.data);
        }
      }).catch(function (error) {
        $('#optionModalError').removeAttr('hidden').text(error.message || 'カートに追加できませんでした。');
      }).finally(function () {
        $button.prop('disabled', false);
      });
    },
    renderCart: function (data) {
      $('#cartDrawerItems').html(data.mini_cart || '');
      $('#cartDrawerEmpty').prop('hidden', data.cart_count > 0);
      $('#cartDrawerCount').text(data.cart_count);
      $('#cartDrawerTotal').html(data.cart_subtotal);
      $('#cartDrawerCheckout')
        .attr('href', data.checkout_url)
        .toggleClass('is-disabled', data.cart_count < 1)
        .attr('aria-disabled', data.cart_count < 1 ? 'true' : 'false');
      $('.cart-menu__badge').text(data.cart_count).toggleClass('is-empty', data.cart_count < 1);
      if (this.drawer) {
        this.drawer.show();
      }
    },
    escape: function (value) {
      return $('<span>').text(value).html();
    }
  };

  var AgeGate = {
    hasConsent: function () {
      return document.cookie.split('; ').some(function (entry) {
        return entry === '7s7_age_verified=yes';
      });
    },
    remember: function () {
      var secure = window.location.protocol === 'https:' ? '; Secure' : '';
      document.cookie = '7s7_age_verified=yes; Max-Age=34560000; Path=/; SameSite=Lax' + secure;
    },
    init: function () {
      var el = document.getElementById('ageGate');
      if (!el || !window.bootstrap) {
        return;
      }
      var modal = window.bootstrap.Modal.getOrCreateInstance(el, { backdrop: 'static', keyboard: false });
      if (this.hasConsent()) {
        return;
      }
      modal.show();
      document.getElementById('ageGateYes').addEventListener('click', function () {
        this.remember();
        modal.hide();
      }.bind(this));
      document.getElementById('ageGateNo').addEventListener('click', function () {
        document.getElementById('ageGateAsk').hidden = true;
        document.getElementById('ageGateDeny').hidden = false;
        document.getElementById('ageGateDeny').focus();
        el.setAttribute('aria-labelledby', 'ageGateDenyTitle');
      });
    }
  };

  var MemberAddresses = {
    init: function () {
      var modalElement = document.getElementById('addrModal');
      if (!modalElement || !window.bootstrap) {
        return;
      }
      var modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
      var form = document.getElementById('addrManagerForm');
      $(document).on('click', '.js-addr-add', function () {
        form.reset();
        form.querySelector('#addrId').value = '';
        form.querySelector('#addrType').value = this.getAttribute('data-type') || 'shipping';
        form.querySelector('#addrModalTitle').textContent = '住所を追加';
        modal.show();
      });
      $(document).on('click', '.js-addr-edit', function () {
        var address = {};
        try {
          address = JSON.parse(this.getAttribute('data-address') || '{}');
        } catch (error) {
          return;
        }
        var fields = {
          addrId: 'id',
          addrType: null,
          addrLastName: 'last_name',
          addrFirstName: 'first_name',
          addrPostcode: 'postcode',
          addrState: 'state',
          addrCity: 'city',
          addrLine1: 'address_1',
          addrLine2: 'address_2',
          addrPhone: 'phone'
        };
        Object.keys(fields).forEach(function (id) {
          var element = document.getElementById(id);
          if (!element) {
            return;
          }
          if (id === 'addrType') {
            element.value = this.getAttribute('data-type') || 'shipping';
          } else {
            element.value = address[fields[id]] || '';
          }
        }, this);
        form.querySelector('#addrDefault').checked = !!address.default;
        form.querySelector('#addrModalTitle').textContent = '住所を変更';
        modal.show();
      });
    }
  };

  var CheckoutAddresses = {
    init: function () {
      if ($(document.body).hasClass('woocommerce-checkout')) {
        window.setTimeout(function () {
          $('.woocommerce-form-coupon-toggle').hide();
          $('form.checkout_coupon').hide();
        }, 120);
      }
      var selector = document.querySelector('[data-address-select="shipping"]');
      if (!selector) {
        return;
      }
      this.different = $('#vkBillDifferent');
      this.billingPanel = $('[data-billing-panel]');
      this.billSame = $('[data-bill-same]');
      this.billingFields = $('.woocommerce-billing-fields__field-wrapper');
      this.apply(this.selected('shipping'));
      this.different.on('change', function () {
        this.billingPanel.prop('hidden', !this.different.is(':checked'));
        this.billSame.toggle(!this.different.is(':checked'));
        this.billingFields.toggle(this.different.is(':checked'));
        if (!this.different.is(':checked')) {
          this.copy('shipping', 'billing');
        } else {
          this.apply(this.selected('billing'), 'billing');
        }
      }.bind(this));
      $(document).on('change', 'input[name="vk_saved_shipping"]', function () {
        var address = {};
        try {
          address = JSON.parse(this.getAttribute('data-address') || '{}');
        } catch (error) {
          return;
        }
        $('input[name="vk_saved_shipping"]').closest('.addr').removeClass('is-selected');
        $(this).closest('.addr').addClass('is-selected');
        this.apply(address, 'shipping');
        if (!this.different.is(':checked')) {
          this.copy('shipping', 'billing');
        }
      }.bind(this));
      $(document).on('change', 'input[name="vk_saved_billing"]', function () {
        var address = {};
        try {
          address = JSON.parse(this.getAttribute('data-address') || '{}');
        } catch (error) {
          return;
        }
        $('input[name="vk_saved_billing"]').closest('.addr').removeClass('is-selected');
        $(this).closest('.addr').addClass('is-selected');
        this.apply(address, 'billing');
      }.bind(this));
      $(document.body).on('updated_checkout', function () {
        this.apply(this.selected('shipping'));
        if (this.different.is(':checked')) {
          this.apply(this.selected('billing'), 'billing');
        } else {
          this.copy('shipping', 'billing');
        }
      }.bind(this));
      $('#ship-to-different-address-checkbox').prop('checked', true).trigger('change');
      this.billingFields.toggle(this.different.is(':checked'));
    },
    selected: function (type) {
      var checked = document.querySelector('input[name="vk_saved_' + type + '"]:checked');
      if (checked) {
        try {
          return JSON.parse(checked.getAttribute('data-address'));
        } catch (error) {
          return null;
        }
      }
      return null;
    },
    apply: function (address, type) {
      type = type || 'shipping';
      if (!address) {
        return;
      }
      var fields = {
        first_name: address.first_name || '',
        last_name: address.last_name || '',
        postcode: address.postcode || '',
        state: address.state || '',
        city: address.city || '',
        address_1: address.address_1 || '',
        address_2: address.address_2 || '',
        phone: address.phone || ''
      };
      var stateChanged = $('#' + type + '_state').val() !== fields.state;
      Object.keys(fields).forEach(function (key) {
        var input = $('#' + type + '_' + key);
        if (input.val() !== fields[key]) {
          input.val(fields[key]);
        }
      });
      $('#' + type + '_country').val('JP');
      if (stateChanged) {
        $('#' + type + '_state').trigger('change');
      }
      if (type === 'shipping' && !this.different.is(':checked')) {
        this.copy('shipping', 'billing');
      }
    },
    copy: function (from, to) {
      ['first_name', 'last_name', 'postcode', 'state', 'city', 'address_1', 'address_2', 'phone'].forEach(function (key) {
        $('#' + to + '_' + key).val($('#' + from + '_' + key).val());
      });
      $('#' + to + '_country').val('JP');
    }
  };

  var CheckoutAddressEditor = {
    init: function () {
      var modalElement = document.getElementById('vkCheckoutAddressModal');
      var form = document.getElementById('vkCheckoutAddressForm');
      if (!modalElement || !form || !window.bootstrap) {
        return;
      }
      var modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
      var title = document.getElementById('vkCheckoutAddressModalTitle');
      var error = form.querySelector('[data-vk-address-error]');
      var submit = form.querySelector('[type="submit"]');

      $(document).on('click', '.js-checkout-address-add', function () {
        form.reset();
        form.querySelector('#vkCheckoutAddressId').value = '';
        form.querySelector('#vkCheckoutAddressType').value = this.getAttribute('data-type') || 'shipping';
        title.textContent = '住所を追加';
        error.hidden = true;
        modal.show();
      });

      $(document).on('click', '.js-checkout-address-edit', function () {
        var address = {};
        try {
          address = JSON.parse(this.getAttribute('data-address') || '{}');
        } catch (e) {
          return;
        }
        form.reset();
        error.hidden = true;
        form.querySelector('#vkCheckoutAddressId').value = address.id || '';
        form.querySelector('#vkCheckoutAddressType').value = this.getAttribute('data-type') || 'shipping';
        form.querySelector('#vkCheckoutAddressLastName').value = address.last_name || '';
        form.querySelector('#vkCheckoutAddressFirstName').value = address.first_name || '';
        form.querySelector('#vkCheckoutAddressPostcode').value = address.postcode || '';
        form.querySelector('#vkCheckoutAddressState').value = address.state || 'JP13';
        form.querySelector('#vkCheckoutAddressCity').value = address.city || '';
        form.querySelector('#vkCheckoutAddressLine1').value = address.address_1 || '';
        form.querySelector('#vkCheckoutAddressLine2').value = address.address_2 || '';
        form.querySelector('#vkCheckoutAddressPhone').value = address.phone || '';
        form.querySelector('#vkCheckoutAddressDefault').checked = !!address.default;
        title.textContent = '住所を変更';
        modal.show();
      });

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!form.reportValidity()) {
          return;
        }
        var type = form.querySelector('#vkCheckoutAddressType').value;
        var targetId = form.querySelector('#vkCheckoutAddressId').value;
        var payload = new FormData(form);
        payload.set('default', form.querySelector('#vkCheckoutAddressDefault').checked ? '1' : '');
        var payloadData = {};
        payload.forEach(function (value, key) {
          payloadData[key] = value;
        });
        payloadData.default = form.querySelector('#vkCheckoutAddressDefault').checked;
        error.hidden = true;
        submit.disabled = true;
        submit.textContent = '保存中…';
        window.fetch(settings.restUrl + 'member-address', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': settings.nonce },
          body: JSON.stringify(payloadData)
        }).then(function (response) {
          return response.json().then(function (data) {
            return { ok: response.ok, data: data };
          });
        }).then(function (result) {
          if (!result.ok) {
            throw new Error(result.data.message || '住所を保存できませんでした。');
          }
          var $container = type === 'billing' ? $('[data-billing-panel] .addr-list') : $('[data-address-select="shipping"] .addr-list');
          $container.html(result.data.listHtml);
          var savedId = result.data.address.id;
          var $target = $container.find('[data-address-id="' + savedId + '"]');
          $container.find('input[type="radio"]').prop('checked', false).closest('.addr').removeClass('is-selected');
          $target.find('input[type="radio"]').prop('checked', true);
          $target.addClass('is-selected');
          $container.find('input[type="radio"]:checked').trigger('change');
          modal.hide();
          showToast(targetId ? '住所を更新しました。' : '住所を追加しました。');
        }).catch(function (saveError) {
          error.textContent = saveError.message || '住所を保存できませんでした。';
          error.hidden = false;
        }).then(function () {
          submit.disabled = false;
          submit.textContent = '住所を保存する';
        });
      });
    }
  };

  var CheckoutGuestAddresses = {
    init: function () {
      if (settings.isLoggedIn) {
        return;
      }
      var emailField = document.getElementById('billing_email_field');
      var emailSlot = document.querySelector('[data-vk-guest-email-slot]');
      if (emailField && emailSlot) {
        emailSlot.appendChild(emailField);
      }

      var $different = $('#vkGuestBillDifferent');
      var $billingFields = $('[data-vk-guest-billing-fields]');
      var $same = $('[data-vk-guest-bill-same]');
      if (!$different.length || !$billingFields.length) {
        return;
      }
      $('#ship-to-different-address-checkbox').prop('checked', true).trigger('change');

      function copyShippingToBilling() {
        ['first_name', 'last_name', 'company', 'postcode', 'state', 'city', 'address_1', 'address_2', 'phone'].forEach(function (field) {
          var source = document.getElementById('shipping_' + field);
          var target = document.getElementById('billing_' + field);
          if (source && target) {
            target.value = source.value;
          }
        });
        $('#billing_country').val('JP');
      }

      function syncBillingMode() {
        var different = $different.is(':checked');
        $billingFields.prop('hidden', !different);
        $same.prop('hidden', different);
        if (!different) {
          copyShippingToBilling();
        }
      }

      $different.on('change', syncBillingMode);
      $(document).on('input change', '#shipping_first_name, #shipping_last_name, #shipping_company, #shipping_postcode, #shipping_state, #shipping_city, #shipping_address_1, #shipping_address_2, #shipping_phone', function () {
        if (!$different.is(':checked')) {
          copyShippingToBilling();
        }
      });
      syncBillingMode();
    }
  };

  var CheckoutCoupon = {
    init: function () {
      var $input = $('#vkCouponCode');
      var $button = $('.vk-checkout-coupon .co-coupon__apply');
      var $message = $('[data-vk-coupon-message]');
      if (!$input.length || !$button.length || !window.wc_checkout_params) {
        return;
      }
      $button.on('click', function () {
        var code = $.trim($input.val());
        if (!code) {
          $message.prop('hidden', false).addClass('is-error').text('クーポンコードを入力してください。');
          $input.trigger('focus');
          return;
        }
        $button.prop('disabled', true);
        $message.prop('hidden', false).removeClass('is-error').text('クーポンを確認しています…');
        $.ajax({
          type: 'POST',
          url: window.wc_checkout_params.wc_ajax_url.toString().replace('%%endpoint%%', 'apply_coupon'),
          data: {
            security: window.wc_checkout_params.apply_coupon_nonce,
            coupon_code: code,
            billing_email: $('input[name="billing_email"]').val() || ''
          },
          dataType: 'html'
        }).done(function (response) {
          var isError = response.indexOf('woocommerce-error') !== -1 || response.indexOf('is-error') !== -1;
          $message.prop('hidden', false).toggleClass('is-error', isError).text($('<div>').html(response).text().trim() || (isError ? 'クーポンを適用できませんでした。' : 'クーポンを適用しました。'));
          if (!isError) {
            $input.val('');
            $(document.body).trigger('update_checkout', { update_shipping_method: false });
          }
        }).fail(function () {
          $message.prop('hidden', false).addClass('is-error').text('クーポンを適用できませんでした。');
        }).always(function () {
          $button.prop('disabled', false);
        });
      });
    }
  };

  var CheckoutCardForm = {
    init: function () {
      var self = this;
      function formatCardNumber(input) {
        var digits = input.value.replace(/\D/g, '').slice(0, 19);
        var formatted = digits.replace(/(.{4})/g, '$1 ').trim();
        if (input.value !== formatted) {
          input.value = formatted;
        }
      }
      function applyPlaceholders() {
        $('#cooder-card-number').attr('placeholder', '1234 5678 9012 3456');
        $('#cooder-card-number').attr('maxlength', '23');
        $('#cooder-card-expiry').attr('placeholder', 'MM / YY');
        $('#cooder-card-cvc').attr('placeholder', '123');
      }
      applyPlaceholders();
      this.splitExpiry();
      window.setTimeout(function () {
        applyPlaceholders();
        self.splitExpiry();
      }, 500);
      $(document).on('input paste', '#cooder-card-number', function () {
        var input = this;
        formatCardNumber(input);
        window.setTimeout(function () {
          formatCardNumber(input);
        }, 0);
      });
      $(document).on('blur change', '#cooder-card-number', function () {
        formatCardNumber(this);
      });
      $(document.body).on('updated_checkout payment_method_selected', function () {
        window.setTimeout(function () {
          applyPlaceholders();
          var input = document.getElementById('cooder-card-number');
          if (input) {
            formatCardNumber(input);
          }
          self.splitExpiry();
        }, 30);
      });
    },
    splitExpiry: function () {
      var source = document.getElementById('cooder-card-expiry');
      if (!source || source.dataset.vkExpiryReady) {
        return;
      }
      var field = source.closest('.form-row');
      if (!field) {
        return;
      }
      source.dataset.vkExpiryReady = '1';
      source.classList.add('vk-card-expiry-source');
      var label = field.querySelector('label');
      if (label) {
        label.classList.add('visually-hidden');
      }
      field.classList.add('vk-expiry-field');

      var now = new Date();
      var currentYear = now.getFullYear();
      var monthOptions = '<option value="">月</option>';
      var yearOptions = '<option value="">年</option>';
      for (var month = 1; month <= 12; month += 1) {
        var monthValue = String(month).padStart(2, '0');
        monthOptions += '<option value="' + monthValue + '">' + monthValue + '</option>';
      }
      for (var year = currentYear; year <= currentYear + 20; year += 1) {
        yearOptions += '<option value="' + year + '">' + year + '</option>';
      }
      var wrapper = document.createElement('div');
      wrapper.className = 'vk-expiry-fields';
      wrapper.innerHTML = '<label class="vk-card-field"><span>有効期限（月）</span><select class="field__select" data-vk-expiry-month>' + monthOptions + '</select></label><label class="vk-card-field"><span>有効期限（年）</span><select class="field__select" data-vk-expiry-year>' + yearOptions + '</select></label>';
      field.appendChild(wrapper);
      var updateSource = function () {
        var month = wrapper.querySelector('[data-vk-expiry-month]').value;
        var year = wrapper.querySelector('[data-vk-expiry-year]').value;
        source.value = month && year ? month + ' / ' + year.slice(-2) : '';
        $(source).trigger('input').trigger('change');
      };
      $(wrapper).on('change', 'select', updateSource);
    }
  };

  var CartSelection = {
    init: function () {
      var form = document.getElementById('cartForm');
      if (!form) {
        return;
      }
      var all = document.getElementById('cartPickAll');
      var items = Array.prototype.slice.call(form.querySelectorAll('.js-cart-pick'));
      if (!items.length) {
        return;
      }
      function update() {
        var selected = items.filter(function (item) { return item.checked; });
        var count = selected.reduce(function (total, item) {
          var input = item.closest('.cart-item').querySelector('input.qty');
          return total + Number(input ? input.value : 1);
        }, 0);
        $('#cartPickCount').text(count);
        if (all) {
          all.checked = selected.length === items.length;
          all.indeterminate = selected.length > 0 && selected.length < items.length;
        }
        $('.cart-summary__checkout').prop('disabled', selected.length === 0)
          .toggleClass('is-disabled', selected.length === 0);
      }
      $(form).on('change', '.js-cart-pick', update);
      if (all) {
        $(all).on('change', function () {
          items.forEach(function (item) {
            if (!item.disabled) {
              item.checked = all.checked;
            }
          });
          update();
        });
      }
      update();
    }
  };

  var ToTop = {
    init: function () {
      $('#toTop').on('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    }
  };

  var WooControls = {
    init: function () {
      $('.pdp-buy--woo .quantity, form.cart .quantity, .woocommerce-cart-form .quantity').each(function () {
        var $quantity = $(this);
        if ($quantity.find('.qty__btn').length) {
          return;
        }
        $quantity.prepend('<button class="qty__btn js-minus" type="button" aria-label="数量を減らす">−</button>');
        $quantity.append('<button class="qty__btn js-plus" type="button" aria-label="数量を増やす">＋</button>');
      });

      $(document).on('click', '.qty__btn', function () {
        var $button = $(this);
        var $input = $button.closest('.quantity, .qty').find('input.qty, input[type="number"][name="quantity"]').first();
        if (!$input.length) {
          return;
        }
        var min = Number($input.attr('min') || 1);
        var max = Number($input.attr('max') || 9999);
        var value = Number($input.val() || min);
        value += $button.hasClass('js-plus') || $button.hasClass('plus') ? 1 : -1;
        $input.val(Math.min(max, Math.max(min, value))).trigger('input').trigger('change');
      });

      $(document).on('click', '.js-goto-tab', function (event) {
        if (!window.bootstrap) {
          return;
        }
        var target = $(this).attr('data-bs-target') || $(this).attr('href');
        var toggle = document.querySelector('[role="tablist"] [data-bs-target="' + target + '"]');
        if (!toggle) {
          return;
        }
        event.preventDefault();
        window.bootstrap.Tab.getOrCreateInstance(toggle).show();
      });

      $('#filterForm input[type="checkbox"], #filterForm select').on('change', function () {
        if (window.matchMedia('(min-width: 992px)').matches) {
          $(this).closest('form').trigger('submit');
        }
      });

      $(document.body).on('added_to_cart', function (event, fragments, cartHash, $button) {
        if ($button && $button.length) {
          showToast('カートに追加しました。');
        }
      });
    }
  };

  $(function () {
    StickyHeader.init();
    HoverDropdown.init();
    SearchSuggest.init();
    Sliders.init();
    Favorites.init();
    Newsletter.init();
    BrandFilter.init();
    ContactForm.init();
    AccountPreferences.init();
    ProductDetail.init();
    CatalogCart.init();
    AgeGate.init();
    MemberAddresses.init();
    CheckoutAddresses.init();
    CheckoutAddressEditor.init();
    CheckoutGuestAddresses.init();
    CheckoutCoupon.init();
    CheckoutCardForm.init();
    CartSelection.init();
    ToTop.init();
    WooControls.init();
    $(window).trigger('scroll');
  });
}(jQuery));
