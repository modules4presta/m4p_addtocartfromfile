/**
 * m4p_addtocartfromfile
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

(function () {
  'use strict';

  var config = window.m4pAddToCartFromFile;
  if (!config) {
    return;
  }

  var labels = config.labels || {};
  var parserPromise = null;

  function t(key) {
    return labels[key] || key;
  }

  function parserReady() {
    if (window.XLSX) {
      return Promise.resolve();
    }

    // About 900 kB, so it waits for the first .xls/.xlsx pick.
    if (!parserPromise) {
      parserPromise = new Promise(function (resolve, reject) {
        var script = document.createElement('script');
        script.src = config.parser;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
      });
    }

    return parserPromise;
  }

  function extensionOf(name) {
    return name.split('.').pop().toLowerCase();
  }

  function splitRows(text) {
    return text
      .split(/\r?\n/)
      .map(function (line) {
        return line.split(';').map(function (cell) {
          return cell.replace(/^"|"$/g, '').trim();
        });
      })
      .filter(function (cells) {
        return cells.some(function (cell) {
          return cell !== '';
        });
      });
  }

  function readFile(file) {
    var extension = extensionOf(file.name);

    if (extension === 'csv') {
      if (!config.csv) {
        return Promise.reject('badFormat');
      }

      return file.text().then(splitRows);
    }

    if (extension === 'xls' || extension === 'xlsx') {
      if (!config.xls) {
        return Promise.reject('badFormat');
      }

      return parserReady()
        .then(function () {
          return file.arrayBuffer();
        })
        .then(function (buffer) {
          var workbook = window.XLSX.read(new Uint8Array(buffer), { type: 'array' });
          var sheet = workbook.Sheets[workbook.SheetNames[0]];

          return splitRows(window.XLSX.utils.sheet_to_csv(sheet, { FS: ';' }));
        })
        .catch(function () {
          return Promise.reject('unreadable');
        });
    }

    return Promise.reject('badFormat');
  }

  function Importer(root) {
    this.root = root;
    this.modal = root.querySelector('[data-m4p-atcff-modal]');
    this.input = root.querySelector('[data-m4p-atcff-input]');
    this.drop = root.querySelector('[data-m4p-atcff-drop]');
    this.filename = root.querySelector('[data-m4p-atcff-filename]');
    this.alert = root.querySelector('[data-m4p-atcff-alert]');
    this.summary = root.querySelector('[data-m4p-atcff-summary]');
    this.tableWrap = root.querySelector('[data-m4p-atcff-tablewrap]');
    this.rows = root.querySelector('[data-m4p-atcff-rows]');
    this.hint = root.querySelector('.m4p-atcff__hint');
    this.submit = root.querySelector('[data-m4p-atcff-submit]');
    this.cancel = root.querySelectorAll('[data-m4p-atcff-close]');
    this.again = root.querySelector('[data-m4p-atcff-again]');
    this.report = root.querySelector('[data-m4p-atcff-report]');
    this.done = root.querySelector('[data-m4p-atcff-done]');
    this.results = [];
    this.bind();
  }

  Importer.prototype.bind = function () {
    var self = this;

    this.root.querySelector('[data-m4p-atcff-open]').addEventListener('click', function () {
      self.open();
    });

    Array.prototype.forEach.call(this.cancel, function (button) {
      button.addEventListener('click', function () {
        self.close();
      });
    });

    this.modal.addEventListener('click', function (event) {
      if (event.target === self.modal) {
        self.close();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !self.modal.hidden) {
        self.close();
      }
    });

    this.input.addEventListener('change', function () {
      self.showFileName();
    });

    ['dragover', 'dragenter'].forEach(function (name) {
      self.drop.addEventListener(name, function (event) {
        event.preventDefault();
        self.drop.classList.add('is-over');
      });
    });

    ['dragleave', 'drop'].forEach(function (name) {
      self.drop.addEventListener(name, function () {
        self.drop.classList.remove('is-over');
      });
    });

    this.drop.addEventListener('drop', function (event) {
      event.preventDefault();
      if (event.dataTransfer.files.length) {
        self.input.files = event.dataTransfer.files;
        self.showFileName();
      }
    });

    this.submit.addEventListener('click', function () {
      self.run();
    });

    this.again.addEventListener('click', function () {
      self.reset();
    });

    this.report.addEventListener('click', function () {
      self.downloadReport();
    });

    this.done.addEventListener('click', function () {
      window.location.reload();
    });
  };

  Importer.prototype.open = function () {
    this.modal.hidden = false;
    document.body.classList.add('m4p-atcff-open');
  };

  Importer.prototype.close = function () {
    this.modal.hidden = true;
    document.body.classList.remove('m4p-atcff-open');

    // The rows are already in the cart, so leaving has to refresh the totals.
    if (this.results.length) {
      window.location.reload();
    }
  };

  Importer.prototype.showFileName = function () {
    var file = this.input.files[0];
    if (!file) {
      return;
    }

    this.filename.innerHTML = '';
    var name = document.createElement('strong');
    name.textContent = t('selected');
    var value = document.createElement('span');
    value.textContent = file.name;
    this.filename.appendChild(name);
    this.filename.appendChild(value);
    this.hideAlert();
  };

  Importer.prototype.showAlert = function (key) {
    this.alert.textContent = t(key);
    this.alert.hidden = false;
  };

  Importer.prototype.hideAlert = function () {
    this.alert.hidden = true;
  };

  Importer.prototype.run = function () {
    var self = this;
    var file = this.input.files[0];

    if (!file) {
      this.showAlert('noFile');

      return;
    }

    this.submit.disabled = true;
    this.submit.dataset.m4pLabel = this.submit.textContent;
    this.submit.textContent = t('working');
    this.hideAlert();

    readFile(file)
      .then(function (rows) {
        var body = rows.slice(1);
        if (!body.length) {
          return Promise.reject('empty');
        }

        // Reported without being sent, so an oversized file names every skipped row.
        var overflow = body.slice(config.maxRows).map(function (row) {
          return {
            label: (row[0] || row[1] || '').toString(),
            requested: 0,
            in_cart: 0,
            status: 'skipped',
          };
        });

        return self.send(body.slice(0, config.maxRows)).then(function (data) {
          return (data.results || []).concat(overflow);
        });
      })
      .then(function (results) {
        self.render(results);
      })
      .catch(function (reason) {
        self.showAlert(typeof reason === 'string' ? reason : 'failed');
      })
      .then(function () {
        self.submit.disabled = false;
        self.submit.textContent = self.submit.dataset.m4pLabel;
      });
  };

  Importer.prototype.send = function (rows) {
    return fetch(config.endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ token: config.token, rows: rows }),
    }).then(function (response) {
      if (!response.ok && response.status !== 200) {
        return Promise.reject('failed');
      }

      return response.json();
    });
  };

  Importer.prototype.render = function (results) {
    this.results = results;
    this.rows.innerHTML = '';

    var counts = { imported: 0, reduced: 0, failed: 0 };

    results.forEach(function (result) {
      if (result.status === 'imported') {
        counts.imported += 1;
      } else if (result.status === 'reduced') {
        counts.reduced += 1;
      } else {
        counts.failed += 1;
      }

      var row = document.createElement('tr');
      row.className = 'm4p-atcff__row is-' + result.status;

      [result.label, result.requested, result.in_cart, t(result.status)].forEach(function (value) {
        var cell = document.createElement('td');
        cell.textContent = value;
        row.appendChild(cell);
      });

      this.rows.appendChild(row);
    }, this);

    this.root.querySelector('[data-m4p-atcff-count-total]').textContent = results.length;
    this.root.querySelector('[data-m4p-atcff-count-added]').textContent = counts.imported;
    this.root.querySelector('[data-m4p-atcff-count-reduced]').textContent = counts.reduced;
    this.root.querySelector('[data-m4p-atcff-count-failed]').textContent = counts.failed;

    this.drop.hidden = true;
    this.hint.hidden = true;
    this.summary.hidden = false;
    this.tableWrap.hidden = false;
    this.submit.hidden = true;
    Array.prototype.forEach.call(this.cancel, function (button) {
      button.hidden = true;
    });
    this.again.hidden = false;
    this.report.hidden = false;
    this.done.hidden = false;
  };

  Importer.prototype.reset = function () {
    this.input.value = '';
    this.filename.innerHTML = '';
    var strong = document.createElement('strong');
    strong.textContent = t('pick');
    var span = document.createElement('span');
    span.textContent = t('drag');
    this.filename.appendChild(strong);
    this.filename.appendChild(span);

    this.rows.innerHTML = '';
    this.drop.hidden = false;
    this.hint.hidden = false;
    this.summary.hidden = true;
    this.tableWrap.hidden = true;
    this.submit.hidden = false;
    Array.prototype.forEach.call(this.cancel, function (button) {
      button.hidden = false;
    });
    this.again.hidden = true;
    this.report.hidden = true;
    this.done.hidden = true;
    this.hideAlert();
  };

  Importer.prototype.downloadReport = function () {
    var head = Array.prototype.map
      .call(this.root.querySelectorAll('.m4p-atcff__table thead th'), function (cell) {
        return cell.textContent;
      })
      .join(';');

    var lines = this.results.map(function (result) {
      return [result.label, result.requested, result.in_cart, t(result.status)].join(';');
    });

    var blob = new Blob(['﻿' + [head].concat(lines).join('\n')], {
      type: 'text/csv;charset=utf-8',
    });
    var url = URL.createObjectURL(blob);
    var link = document.createElement('a');
    link.href = url;
    link.download = t('reportName') + '.csv';
    link.click();
    URL.revokeObjectURL(url);
  };

  document.addEventListener('DOMContentLoaded', function () {
    Array.prototype.forEach.call(document.querySelectorAll('.m4p-atcff'), function (root) {
      new Importer(root);
    });
  });
})();
