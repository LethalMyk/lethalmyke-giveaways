import './bootstrap';

const searchInput = document.getElementById('tool-search');
if (searchInput) {
    searchInput.addEventListener('input', (event) => {
        const query = event.target.value.trim().toLowerCase();
        const cards = document.querySelectorAll('a[href*="tools"]');

        cards.forEach((card) => {
            const text = card.textContent.toLowerCase();
            card.style.display = text.includes(query) || !query ? 'block' : 'none';
        });
    });
}

const charmForm = document.getElementById('charm-calculator-form');
if (charmForm) {
    const selectedList = document.getElementById('selected-charms');
    const availableInput = document.getElementById('available-points');
    const resultBox = document.getElementById('charm-result');

    const updateCharmResult = () => {
        const selected = [...document.querySelectorAll('input[name="charms[]"]:checked')].map((input) => ({
            cost: Number(input.dataset.cost ?? 0),
            name: input.dataset.name ?? 'Charm',
        }));

        const available = Number(availableInput?.value ?? 0);
        const required = selected.reduce((sum, item) => sum + item.cost, 0);
        const difference = available - required;

        selectedList.textContent = selected.length
            ? selected.map((item) => `${item.name} (${item.cost} pts)`).join(', ')
            : 'Nenhum charm selecionado.';

        resultBox.innerHTML = `
            <p><strong>Pontos necessários:</strong> ${required}</p>
            <p><strong>Disponíveis:</strong> ${available}</p>
            <p><strong>Diferença:</strong> ${difference}</p>
            <p class="mt-2 ${difference >= 0 ? 'text-emerald-300' : 'text-amber-300'}">
                ${difference >= 0 ? `Você ainda terá ${difference} pontos sobrando.` : `Faltam ${Math.abs(difference)} pontos.`}
            </p>
        `;
    };

    charmForm.addEventListener('change', updateCharmResult);
    availableInput.addEventListener('input', updateCharmResult);
    updateCharmResult();
}

const imbuementForm = document.getElementById('imbuement-calculator-form');
if (imbuementForm) {
    const resultBox = document.getElementById('imbuement-result');
    const inputs = [...document.querySelectorAll('[data-material-cost]')];

    const updateImbuementResult = () => {
        const entries = inputs.map((input) => ({
            name: input.dataset.materialName ?? 'Material',
            quantity: Number(input.value || 0),
            unit_price: Number(input.dataset.materialCost || 0),
        }));

        const total = entries.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
        const lines = entries.map((item) => `${item.name}: ${item.quantity} × ${item.unit_price} = ${item.quantity * item.unit_price} gold`).join('<br>');

        resultBox.innerHTML = `<div class="space-y-2"><p><strong>Total estimado:</strong> ${total} gold</p><p>${lines || 'Nenhum material informado.'}</p></div>`;
    };

    inputs.forEach((input) => input.addEventListener('input', updateImbuementResult));
    updateImbuementResult();
}

const exerciseWeaponForm = document.getElementById('exercise-weapon-form');
if (exerciseWeaponForm) {
    const statusLabel = document.getElementById('exercise-calculation-status');
    const errorBox = document.getElementById('exercise-calculation-error');
    const numberFormat = new Intl.NumberFormat('pt-BR');
    let requestController;
    let debounceTimer;

    const updateRangeLabels = () => {
        document.getElementById('remaining-percent-label').textContent = `${exerciseWeaponForm.elements.remaining_percent.value}%`;
        document.getElementById('loyalty-percent-label').textContent = `${exerciseWeaponForm.elements.loyalty_percent.value}%`;
    };

    const formatDuration = (seconds) => {
        const days = Math.floor(seconds / 86400);
        const hours = Math.floor((seconds % 86400) / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        return [days ? `${days}d` : null, hours ? `${hours}h` : null, `${minutes}min`]
            .filter(Boolean)
            .join(' ');
    };

    const syncUrl = () => {
        const url = new URL(window.location.href);
        url.searchParams.set('vocation', exerciseWeaponForm.querySelector('[name="vocation"]:checked').value);
        url.searchParams.set('skill', exerciseWeaponForm.querySelector('[name="skill"]:checked').value);
        url.searchParams.set('current', exerciseWeaponForm.elements.current_skill.value);
        url.searchParams.set('target', exerciseWeaponForm.elements.target_skill.value);
        url.searchParams.set('remaining', exerciseWeaponForm.elements.remaining_percent.value);
        url.searchParams.set('loyalty', exerciseWeaponForm.elements.loyalty_percent.value);
        url.searchParams.set('dummy', exerciseWeaponForm.elements.dummy.checked);
        url.searchParams.set('double-event', exerciseWeaponForm.elements.double_event.checked);
        window.history.replaceState(null, '', url);
    };

    const calculate = async () => {
        requestController?.abort();
        requestController = new AbortController();
        statusLabel.textContent = 'Calculando…';
        errorBox.classList.add('hidden');

        const payload = new URLSearchParams(new FormData(exerciseWeaponForm));
        payload.set('dummy', exerciseWeaponForm.elements.dummy.checked ? '1' : '0');
        payload.set('double_event', exerciseWeaponForm.elements.double_event.checked ? '1' : '0');

        try {
            const response = await fetch(exerciseWeaponForm.action, {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: payload,
                signal: requestController.signal,
            });
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Confira os valores informados.');
            }

            document.getElementById('result-points').textContent = numberFormat.format(result.required_points);
            document.getElementById('result-charges').textContent = numberFormat.format(result.charges_required);
            document.getElementById('result-duration').textContent = formatDuration(result.duration_seconds);
            document.getElementById('result-cost').textContent = `${numberFormat.format(result.gold_estimate)} gold`;
            document.getElementById('weapons-lasting').textContent = `${numberFormat.format(result.weapons.lasting.count)}x`;
            document.getElementById('weapons-durable').textContent = `${numberFormat.format(result.weapons.durable.count)}x`;
            document.getElementById('weapons-regular').textContent = `${numberFormat.format(result.weapons.regular.count)}x`;
            statusLabel.textContent = `Multiplicador ${result.training_multiplier.toFixed(2)}x`;
            syncUrl();
        } catch (error) {
            if (error.name === 'AbortError') return;
            errorBox.textContent = error.message;
            errorBox.classList.remove('hidden');
            statusLabel.textContent = 'Revise os parâmetros';
        }
    };

    const scheduleCalculation = () => {
        updateRangeLabels();
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(calculate, 140);
    };

    exerciseWeaponForm.addEventListener('submit', (event) => {
        event.preventDefault();
        calculate();
    });
    exerciseWeaponForm.addEventListener('input', scheduleCalculation);
    exerciseWeaponForm.addEventListener('change', scheduleCalculation);
    updateRangeLabels();
    calculate();
}

const huntPartyType = document.getElementById('party-type');
if (huntPartyType) {
    const partySizeField = document.getElementById('party-size-field');
    const partySizeInput = document.getElementById('party-size');
    const updatePartySizeField = () => {
        const isTeam = huntPartyType.value === 'team';
        partySizeField.classList.toggle('hidden', !isTeam);
        partySizeInput.required = isTeam;
        if (!isTeam) partySizeInput.value = '';
    };

    huntPartyType.addEventListener('change', updatePartySizeField);
    updatePartySizeField();
}

const copyHuntUrlButton = document.querySelector('[data-copy-url]');
if (copyHuntUrlButton) {
    copyHuntUrlButton.addEventListener('click', async () => {
        const buttonLabel = copyHuntUrlButton.textContent;

        try {
            await navigator.clipboard.writeText(window.location.href);
            copyHuntUrlButton.textContent = 'Link copiado';
        } catch {
            copyHuntUrlButton.textContent = 'Não foi possível copiar';
        }

        window.setTimeout(() => {
            copyHuntUrlButton.textContent = buttonLabel;
        }, 1800);
    });
}

