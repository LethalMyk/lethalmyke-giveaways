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

