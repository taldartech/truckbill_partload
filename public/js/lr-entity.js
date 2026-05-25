(function () {
  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function renderDetailsHtmlFromObject(data, options = {}) {
    const { title = 'All Details', hideDeclarationGiven = false } = options;

    if (data === null || data === undefined) {
      return `<div class="text-muted">No details available.</div>`;
    }

    const getSafe = (value) => {
      if (value === null || value === undefined) {
        return 'Not Set';
      }

      if (Array.isArray(value)) {
        return value.length ? value.join(', ') : 'Not Set';
      }

      return value;
    };

    const yesNo = (value) => {
      if (value === null || value === undefined) {
        return 'Not Set';
      }

      return value ? 'Yes' : 'No';
    };

    const tdsValue = data.tds_percentage ?? data.tdsPercentage ?? null;
    const tdsDisplay = tdsValue === null || tdsValue === undefined || tdsValue === '' ? 'Not Set' : `${tdsValue}%`;

    // Common fields for Transporter / Truck Owner.
    // If the backend returns slightly different structures, we still try to display useful values.
    const name = getSafe(
      data.name ??
        data.transporterMaster?.name ??
        data.transporter_master?.name
    );
    const email = getSafe(
      data.email ??
        data.transporterMaster?.email ??
        data.transporter_master?.email
    );
    const pan = getSafe(
      data.pan_number ??
        data.transporterMaster?.pan_number ??
        data.transporter_master?.pan_number
    );
    const gst = getSafe(
      data.gst_number ??
        data.transporterMaster?.gst_number ??
        data.transporter_master?.gst_number
    );
    const mobile = getSafe(data.mobile_number);
    const contactPerson = getSafe(data.contact_person_name);
    const beneficiaryId = getSafe(data.beneficiary_id);
    const declarationGiven = yesNo(data.declaration_given);
    const shortName = getSafe(data.short_name);
    const gstType = getSafe(
      data.gst_type ?? data.transporterMaster?.gst_type ?? data.transporter_master?.gst_type
    );

    const address = getSafe(
      data.address ?? data.transporterMaster?.address ?? data.transporter_master?.address
    );

    const tm = data.transporterMaster ?? data.transporter_master ?? null;
    const cityFromRelation = tm?.city?.name;
    const stateFromRelation = tm?.state?.name;
    const countryFromRelation = tm?.country?.name;

    const cityDisplay = getSafe(
      (typeof data.city === 'string' ? data.city : null) ??
        cityFromRelation ??
        data.transporterMaster?.city?.name ??
        data.transporter_master?.city?.name
    );
    const stateDisplay = getSafe(
      (typeof data.state === 'string' ? data.state : null) ??
        stateFromRelation ??
        data.transporterMaster?.state?.name ??
        data.transporter_master?.state?.name
    );
    const countryDisplay = getSafe(
      (typeof data.country === 'string' ? data.country : null) ??
        countryFromRelation ??
        data.transporterMaster?.country?.name ??
        data.transporter_master?.country?.name
    );

    let json = '';
    try {
      json = JSON.stringify(data, null, 2);
    } catch (e) {
      json = String(data);
    }

    const escapedJson = escapeHtml(json);

    // Used to draw attention in the confirmation modal.
    const keyHighlight = (value) => `
      <span class="badge bg-warning text-dark" style="word-break: break-word; font-size: 1rem !important;">${escapeHtml(value)}</span>
    `;

    return `
      <div class="mt-2">
        <div class="mb-2">
          <strong>${escapeHtml(title)}</strong>
        </div>

        <div class="row g-2">
          <div class="col-12 col-md-6">
            <p class="mb-1"><strong>Name:</strong> ${keyHighlight(name)}</p>
            <p class="mb-1"><strong>Mobile:</strong> ${keyHighlight(mobile)}</p>
            <p class="mb-1"><strong>Email:</strong> ${escapeHtml(email)}</p>
            <p class="mb-1"><strong>Contact Person:</strong> ${escapeHtml(contactPerson)}</p>
          </div>

          <div class="col-12 col-md-6">
            <p class="mb-1"><strong>PAN:</strong> ${escapeHtml(pan)}</p>
            <p class="mb-1"><strong>GST:</strong> ${escapeHtml(gst)}</p>
            <p class="mb-1"><strong>GST Type:</strong> ${escapeHtml(gstType)}</p>
            <p class="mb-1"><strong>TDS Percentage:</strong> ${escapeHtml(tdsDisplay)}</p>
            ${
              hideDeclarationGiven
                ? ''
                : `<p class="mb-1"><strong>Declaration Given:</strong> ${escapeHtml(declarationGiven)}</p>`
            }
          </div>

          <div class="col-12">
            <p class="mb-1"><strong>Beneficiary ID:</strong> ${keyHighlight(beneficiaryId)}</p>
            <p class="mb-1"><strong>Short Name:</strong> ${keyHighlight(shortName)}</p>
            <p class="mb-1"><strong>Address:</strong> ${escapeHtml(address)}</p>
            <p class="mb-1"><strong>City:</strong> ${escapeHtml(cityDisplay)} &nbsp; <strong>State:</strong> ${escapeHtml(stateDisplay)}</p>
          </div>
        </div>
      </div>
    `;
  }

  function ConfirmEntitySelectionModal({ title, detailsHtml, onConfirm, onCancel }) {
    if (typeof Swal !== 'undefined' && Swal && typeof Swal.fire === 'function') {
      Swal.fire({
        title,
        html: detailsHtml,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '✅ Confirm',
        confirmButtonColor: '#16a34a',
        cancelButtonText: '❌ Cancel',
        cancelButtonColor: '#dc2626',
        focusCancel: true,
        allowOutsideClick: false,
        didOpen: () => {
          setTimeout(() => {
            const cancelButton = Swal.getCancelButton();
            cancelButton?.focus();
          }, 50);
        },
        customClass: {
          popup: 'swal-wide'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          onConfirm?.();
        } else {
          onCancel?.();
        }
      });

      return;
    }

    // Fallback when SweetAlert2 is missing.
    const ok = confirm(title);
    if (ok) {
      onConfirm?.();
    } else {
      onCancel?.();
    }
  }

  function removeHiddenTransporterIdInputs() {
    document.querySelectorAll('input[type="hidden"][name="transporter_id"]').forEach((el) => el.remove());
  }

  function resetEditTruckOwnerLinkHref() {
    const editTruckOwnerLink = document.getElementById('editTruckOwnerLink');
    if (editTruckOwnerLink) {
      editTruckOwnerLink.href = '#';
    }
  }

  /**
   * Mirrors the LR create/edit pages: updates TDS display + declaration warning styling.
   */
  function showTdsPercentage(transporterId) {
    const tdsDisplay = document.getElementById('tdsRateDisplay');
    const tdsValue = document.getElementById('tdsPercentageValue');
    const declarationWarning = document.getElementById('declarationWarning');
    const editTruckOwnerLink = document.getElementById('editTruckOwnerLink');
    const transporterSelect = document.getElementById('transporter_id');
    const transporterSelect2 = window.jQuery ? window.jQuery('#transporter_id') : null;

    if (!tdsDisplay || !tdsValue) {
      return;
    }

    function removeHighlight() {
      if (transporterSelect) {
        transporterSelect.classList.remove('declaration-warning');
      }

      if (!transporterSelect2 || typeof transporterSelect2.next !== 'function') {
        return;
      }

      let select2Container = transporterSelect2.next('.select2-container');
      if (!select2Container.length) {
        select2Container = window.jQuery('.select2-container').has('#transporter_id');
      }
      if (!select2Container.length) {
        select2Container = transporterSelect2.closest('.select2-container');
      }
      if (select2Container.length) {
        select2Container.removeClass('declaration-warning');
      } else if (transporterSelect && transporterSelect.parentElement) {
        const container = transporterSelect.parentElement.querySelector('.select2-container');
        if (container) {
          container.classList.remove('declaration-warning');
        }
      }
    }

    function addHighlight() {
      if (transporterSelect) {
        transporterSelect.classList.add('declaration-warning');
      }

      if (!transporterSelect2 || typeof transporterSelect2.next !== 'function') {
        return;
      }

      let select2Container = transporterSelect2.next('.select2-container');
      if (!select2Container.length) {
        select2Container = window.jQuery('.select2-container').has('#transporter_id');
      }
      if (!select2Container.length) {
        select2Container = transporterSelect2.closest('.select2-container');
      }
      if (select2Container.length) {
        select2Container.addClass('declaration-warning');
      } else if (transporterSelect && transporterSelect.parentElement) {
        const container = transporterSelect.parentElement.querySelector('.select2-container');
        if (container) {
          container.classList.add('declaration-warning');
        }
      }
    }

    const id = transporterId ? String(transporterId) : '';

    if (id) {
      tdsDisplay.style.display = 'block';
      tdsValue.textContent = 'Loading...';
      if (declarationWarning) {
        declarationWarning.style.display = 'none';
      }
      removeHighlight();

      fetch(`/api/transporters/${encodeURIComponent(id)}`)
        .then((response) => response.json())
        .then((data) => {
          if (data.tds_percentage !== null && data.tds_percentage !== undefined) {
            tdsValue.textContent = `${parseFloat(data.tds_percentage).toFixed(2)}%`;
          } else {
            tdsValue.textContent = 'Not Set';
          }

          if (data.is_owner && !data.declaration_given) {
            if (declarationWarning) {
              declarationWarning.style.display = 'block';
            }
            if (editTruckOwnerLink) {
              editTruckOwnerLink.href = `/truck_owners/${encodeURIComponent(id)}/edit`;
            }
            addHighlight();
          } else {
            if (declarationWarning) {
              declarationWarning.style.display = 'none';
            }
            removeHighlight();
          }
        })
        .catch((error) => {
          console.error('Error fetching transporter TDS percentage:', error);
          tdsValue.textContent = 'Error';
          if (declarationWarning) {
            declarationWarning.style.display = 'none';
          }
          removeHighlight();
        });

      return;
    }

    tdsDisplay.style.display = 'none';
    if (declarationWarning) {
      declarationWarning.style.display = 'none';
    }
    removeHighlight();
  }

  function applyTransporterSelection({
    transporterSelect,
    transporterSelect2,
    transporterId,
    transporterName,
    disableSelect,
    disableTitle,
  }) {
    if (!transporterSelect || !transporterId) {
      return;
    }

    transporterSelect.innerHTML = '<option value="">Select Transporter</option>';

    const option = document.createElement('option');
    option.value = String(transporterId);
    option.textContent = transporterName || '';
    option.selected = true;
    transporterSelect.appendChild(option);

    transporterSelect.value = String(transporterId);
    transporterSelect.disabled = !!disableSelect;
    transporterSelect.style.backgroundColor = disableSelect ? '#e9ecef' : '';
    transporterSelect.title = disableTitle || '';

    removeHiddenTransporterIdInputs();

    if (transporterSelect.form) {
      const hiddenInput = document.createElement('input');
      hiddenInput.type = 'hidden';
      hiddenInput.name = 'transporter_id';
      hiddenInput.value = String(transporterId);
      transporterSelect.form.appendChild(hiddenInput);
    }

    if (transporterSelect2 && typeof transporterSelect2.trigger === 'function') {
      transporterSelect2.trigger('change.select2');
    }
  }

  function applyOwnerSelection({
    transporterSelect,
    transporterSelect2,
    ownerId,
    ownerName,
  }) {
    if (!transporterSelect || !ownerId) {
      return;
    }

    transporterSelect.innerHTML = '<option value="">Select Transporter</option>';

    const option = document.createElement('option');
    option.value = String(ownerId);
    option.textContent = ownerName || '';
    option.selected = true;
    transporterSelect.appendChild(option);

    transporterSelect.value = String(ownerId);
    transporterSelect.disabled = true;
    transporterSelect.style.backgroundColor = '#e9ecef';
    transporterSelect.title = 'Automatically selected based on truck owner';

    removeHiddenTransporterIdInputs();
    if (transporterSelect.form) {
      const hiddenInput = document.createElement('input');
      hiddenInput.type = 'hidden';
      hiddenInput.name = 'transporter_id';
      hiddenInput.value = String(ownerId);
      transporterSelect.form.appendChild(hiddenInput);
    }

    if (transporterSelect2 && typeof transporterSelect2.trigger === 'function') {
      transporterSelect2.trigger('change.select2');
    }
  }

  function clearTruckAndTransporterForOwnerCancel({ transporterSelect, transporterSelect2, truckSelect2 }) {
    removeHiddenTransporterIdInputs();

    if (transporterSelect) {
      transporterSelect.innerHTML = '<option value="">Select Transporter</option>';
      transporterSelect.value = '';
      transporterSelect.disabled = false;
      transporterSelect.style.backgroundColor = '';
      transporterSelect.title = '';
    }

    if (transporterSelect2 && typeof transporterSelect2.trigger === 'function') {
      transporterSelect2.trigger('change.select2');
    }

    if (truckSelect2 && typeof truckSelect2.val === 'function') {
      truckSelect2.val(null).trigger('change.select2');
    }
  }

  function clearAgencySelection({
    transporterSelect,
    transporterSelect2,
    transporterTypeSelect,
  }) {
    if (!transporterSelect) {
      return;
    }

    removeHiddenTransporterIdInputs();
    transporterSelect.value = '';
    transporterSelect.disabled = false;
    transporterSelect.style.backgroundColor = '';
    transporterSelect.title = '';

    if (transporterTypeSelect) {
      transporterTypeSelect.value = '';
    }

    if (transporterSelect2 && typeof transporterSelect2.trigger === 'function') {
      transporterSelect2.trigger('change.select2');
    }
  }

  function initLorryReceiptTransporterSelectionFlow(options) {
    const {
      transporterTypeSelect,
      truckSelect,
      transporterSelect,
      transporterLabel,
      trucksData: initialTrucksData = {},
      mode = 'create', // create | edit
      allowTransporterChanges = true,
    } = options;

    if (!transporterTypeSelect || !truckSelect || !transporterSelect || !transporterLabel) {
      return;
    }

    if (!allowTransporterChanges) {
      return;
    }

    if (!window.jQuery) {
      console.error('LR transporter flow requires jQuery for Select2 integration.');
      return;
    }

    const $ = window.jQuery;
    const truckSelect2 = $('#truck_id');
    const transporterSelect2 = $(transporterSelect);

    const trucksData = initialTrucksData && typeof initialTrucksData === 'object' ? initialTrucksData : {};

    let clearingDueToCancel = false;
    let confirmSeq = 0;

    const runAfterClearingCancel = (fn) => {
      clearingDueToCancel = true;
      try {
        fn();
      } finally {
        setTimeout(() => {
          clearingDueToCancel = false;
        }, 0);
      }
    };

    const setOwnerOptionAvailability = (isOwnerAvailable) => {
      const ownerOption = transporterTypeSelect.querySelector('option[value="owner"]');
      if (!ownerOption) {
        return;
      }

      ownerOption.disabled = !isOwnerAvailable;
      if (!isOwnerAvailable && transporterTypeSelect.value === 'owner') {
        transporterTypeSelect.value = 'agency';
      }
    };

    const enforceAgencyForOwnerlessTruck = (truckId) => {
      const transporterId = trucksData[truckId]?.transporterId ?? trucksData[truckId]?.transporter_id ?? null;
      const ownerAvailable = transporterId !== null && transporterId !== undefined && transporterId !== '';
      setOwnerOptionAvailability(ownerAvailable);

      if (!ownerAvailable) {
        transporterTypeSelect.value = 'agency';
        transporterLabel.textContent = 'Agency *';
      }

      return ownerAvailable;
    };

    $('#truck_id').on('select2:select', function (e) {
      if (clearingDueToCancel) {
        return;
      }

      const selectedTruckId = e.params.data.id;
      const data = e.params.data || {};

      const truckText = (data.text || '').toString().trim() || $(this).find('option:selected').text().trim();
      const transporterId = data.transporter_id !== undefined && data.transporter_id !== null && data.transporter_id !== ''
        ? parseInt(data.transporter_id, 10)
        : null;

      const transporterNameFromData = (data.transporter_name || '').toString().trim();
      const transporterMatch = truckText.match(/\(([^)]+)\)$/);
      const transporterName = transporterNameFromData || (transporterMatch ? transporterMatch[1].trim() : '');

      trucksData[selectedTruckId] = {
        transporterId,
        transporterName,
        truckText,
      };

      const ownerAvailable = enforceAgencyForOwnerlessTruck(selectedTruckId);

      if (transporterTypeSelect.value === 'owner' && ownerAvailable) {
        autoSelectTruckOwner(selectedTruckId);
      }
    });

    $('#truck_id').on('change', function () {
      if (clearingDueToCancel) {
        return;
      }

      const selectedTruckId = $(this).val();
      if (!selectedTruckId || trucksData[selectedTruckId]) {
        return;
      }

      const select2Data = truckSelect2 && typeof truckSelect2.select2 === 'function'
        ? (truckSelect2.select2('data') || [])[0] || {}
        : {};

      const truckText = (select2Data.text || '').toString().trim() || $(this).find('option:selected').text().trim();
      const transporterId = select2Data.transporter_id !== undefined && select2Data.transporter_id !== null && select2Data.transporter_id !== ''
        ? parseInt(select2Data.transporter_id, 10)
        : null;

      const transporterNameFromData = (select2Data.transporter_name || '').toString().trim();
      const transporterMatch = truckText.match(/\(([^)]+)\)$/);
      const transporterName = transporterNameFromData || (transporterMatch ? transporterMatch[1].trim() : '');

      trucksData[selectedTruckId] = {
        transporterId,
        transporterName,
        truckText,
      };

      const ownerAvailable = enforceAgencyForOwnerlessTruck(selectedTruckId);

      if (transporterTypeSelect.value === 'owner' && ownerAvailable) {
        autoSelectTruckOwner(selectedTruckId);
      }
    });

    transporterTypeSelect.addEventListener('change', function () {
      const selectedType = this.value;
      const selectedTruckId = truckSelect.value;

      document.querySelectorAll('input[type="hidden"][name="transporter_id"]').forEach((el) => el.remove());

      transporterSelect.innerHTML = '<option value="">Select Transporter</option>';
      transporterSelect.disabled = false;
      transporterSelect2.trigger('change.select2');

      transporterLabel.textContent = selectedType === 'owner' ? 'Truck Owner *' : 'Agency *';

      if (selectedType === 'owner') {
        const ownerAvailable = enforceAgencyForOwnerlessTruck(selectedTruckId);
        if (!ownerAvailable) {
          transporterSelect2.trigger('change.select2');
          return;
        }

        if (selectedTruckId) {
          autoSelectTruckOwner(selectedTruckId);
        }
      } else if (selectedType === 'agency') {
        // Agencies are loaded via Select2 AJAX (no full list fetch).
        transporterSelect2.trigger('change.select2');
      }
    });

    function autoSelectTruckOwner(truckId) {
      if (!truckId) {
        return;
      }

      const seq = ++confirmSeq;

      fetch(`/api/trucks/${encodeURIComponent(String(truckId))}/owner-transporters`)
        .then((response) => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then((data) => {
          const owner = Array.isArray(data) && data.length > 0 ? data[0] : null;

          if (!owner || !owner.id) {
            if (seq !== confirmSeq) {
              return;
            }

            transporterSelect.innerHTML = '<option value="">Select Transporter</option>';
            transporterSelect.disabled = false;
            transporterSelect.style.backgroundColor = '';
            transporterSelect.title = '';
            transporterSelect2.trigger('change.select2');
            removeHiddenTransporterIdInputs();
            showTdsPercentage(null);
            return;
          }

          const ownerId = owner.id;

          return Promise.all([
            fetch(`/api/transporters/${encodeURIComponent(String(ownerId))}`).then((r) => (r.ok ? r.json() : null)),
            fetch(`/api/trucks/${encodeURIComponent(String(truckId))}`).then((r) => (r.ok ? r.json() : null)),
          ]).then(([transporterDetails]) => {
            if (seq !== confirmSeq) {
              return;
            }

            if (!transporterDetails) {
              if (mode === 'edit') {
                applyOwnerSelection({
                  transporterSelect,
                  transporterSelect2,
                  ownerId,
                  ownerName: owner.name || '',
                });
              } else {
                applyTransporterSelection({
                  transporterSelect,
                  transporterSelect2,
                  transporterId: ownerId,
                  transporterName: owner.name || '',
                  disableSelect: true,
                  disableTitle: 'Automatically selected based on truck owner',
                });
              }

              showTdsPercentage(ownerId);
              return;
            }

            const entityName =
              transporterDetails.name ||
              transporterDetails?.transporterMaster?.name ||
              transporterDetails?.transporter_master?.name ||
              owner.name ||
              'N/A';

            const completeDetailsHtml = renderDetailsHtmlFromObject(transporterDetails, {
              title: 'Complete Transporter/Truck Owner Details',
            });

            const detailsHtml = `
              <div class="text-start">
                ${completeDetailsHtml}
              </div>
            `;

            ConfirmEntitySelectionModal({
              title: 'Confirm Truck Owner Selection',
              detailsHtml,
              onConfirm: () => {
                if (seq !== confirmSeq) {
                  return;
                }

                if (mode === 'edit') {
                  applyOwnerSelection({
                    transporterSelect,
                    transporterSelect2,
                    ownerId,
                    ownerName: entityName,
                  });
                } else {
                  applyTransporterSelection({
                    transporterSelect,
                    transporterSelect2,
                    transporterId: ownerId,
                    transporterName: entityName,
                    disableSelect: true,
                    disableTitle: 'Automatically selected based on truck owner',
                  });
                }

                showTdsPercentage(ownerId);
              },
              onCancel: () => {
                if (seq !== confirmSeq) {
                  return;
                }

                runAfterClearingCancel(() => {
                  clearTruckAndTransporterForOwnerCancel({
                    transporterSelect,
                    transporterSelect2,
                    truckSelect2,
                  });
                  if (transporterTypeSelect) {
                    transporterTypeSelect.value = '';
                  }
                  showTdsPercentage(null);
                  resetEditTruckOwnerLinkHref();
                });
              },
            });
          });
        })
        .catch((error) => {
          console.error('❌ Error fetching truck owner:', error);

          if (seq !== confirmSeq) {
            return;
          }

          transporterSelect.innerHTML = '<option value="">Select Transporter</option>';
          transporterSelect.disabled = false;
          transporterSelect.style.backgroundColor = '';
          transporterSelect.title = '';
          transporterSelect2.trigger('change.select2');
          removeHiddenTransporterIdInputs();
          showTdsPercentage(null);
        });
    }

    function handleAgencySelection(transporterId) {
      if (!transporterId) {
        showTdsPercentage(null);
        return;
      }

      const truckId = truckSelect.value;
      const seq = ++confirmSeq;

      const transporterFetch = fetch(`/api/transporters/${encodeURIComponent(String(transporterId))}`).then((r) =>
        r.ok ? r.json() : null
      );
      const truckFetch = truckId
        ? fetch(`/api/trucks/${encodeURIComponent(String(truckId))}`).then((r) => (r.ok ? r.json() : null))
        : Promise.resolve(null);

      Promise.all([transporterFetch, truckFetch])
        .then(([transporterDetails]) => {
          if (seq !== confirmSeq) {
            return;
          }

          if (!transporterDetails) {
            showTdsPercentage(transporterId);
            return;
          }

          const completeDetailsHtml = renderDetailsHtmlFromObject(transporterDetails, {
            title: 'Complete Transporter Details',
            hideDeclarationGiven: true,
          });

          const detailsHtml = `
            <div class="text-start">
              ${completeDetailsHtml}
            </div>
          `;

          ConfirmEntitySelectionModal({
            title: 'Confirm Agency Selection',
            detailsHtml,
            onConfirm: () => {
              if (seq !== confirmSeq) {
                return;
              }
              showTdsPercentage(transporterId);
            },
            onCancel: () => {
              if (seq !== confirmSeq) {
                return;
              }

              runAfterClearingCancel(() => {
                clearAgencySelection({
                  transporterSelect,
                  transporterSelect2,
                  transporterTypeSelect,
                });
                showTdsPercentage(null);
                resetEditTruckOwnerLinkHref();
              });
            },
          });
        })
        .catch(() => {
          showTdsPercentage(transporterId);
        });
    }

    transporterSelect2.on('select2:select', function (e) {
      if (clearingDueToCancel) {
        return;
      }

      if (transporterTypeSelect.value !== 'agency') {
        return;
      }

      const selectedId = e?.params?.data?.id;
      handleAgencySelection(selectedId);
    });

    transporterSelect.addEventListener('change', function () {
      if (clearingDueToCancel) {
        return;
      }

      const id = this.value;

      if (transporterTypeSelect.value === 'owner') {
        return;
      }

      if (transporterTypeSelect.value === 'agency') {
        if (typeof $.fn.select2 !== 'undefined') {
          return;
        }

        handleAgencySelection(id);
        return;
      }

      showTdsPercentage(id);
    });

    // Edit page previously also confirmed agency on native change even with Select2; behavior is unified to Select2 primary + native fallback when Select2 is absent.
    if (truckSelect.value) {
      enforceAgencyForOwnerlessTruck(truckSelect.value);
    } else {
      setOwnerOptionAvailability(true);
    }
  }

  function showValidationError(element, message) {
    if (!element) {
      return;
    }

    clearValidationError(element);
    element.classList.add('is-invalid');

    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback d-block';
    errorDiv.textContent = message;
    errorDiv.id = `${element.id}_validation_error`;

    element.parentNode.insertBefore(errorDiv, element.nextSibling);
  }

  function clearValidationError(element) {
    if (!element) {
      return;
    }

    element.classList.remove('is-invalid');
    const existingError = document.getElementById(`${element.id}_validation_error`);
    if (existingError) {
      existingError.remove();
    }
  }

  window.LREntity = {
    escapeHtml,
    renderDetailsHtmlFromObject,
    ConfirmEntitySelectionModal,
    showTdsPercentage,
    initLorryReceiptTransporterSelectionFlow,
    showValidationError,
    clearValidationError,
  };
})();

