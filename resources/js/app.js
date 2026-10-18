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

const charmOptimizer = document.getElementById('charm-optimizer');
        if (charmOptimizer) {
            const csrfToken = document.getElementById('charm-csrf-token').value;
            const searchInput = document.getElementById('monster-search');
            const charmSearchInput = document.getElementById('charm-search');
            const searchResults = document.getElementById('monster-search-results');
            const selectedMonstersBox = document.getElementById('selected-monsters');
            const huntPresetBox = document.getElementById('hunt-preset-summary');
            const errorBox = document.getElementById('charm-calculation-error');
            const calculationStatus = document.getElementById('charm-calculation-status');
            const inventoryStatus = document.getElementById('charm-inventory-status');
            const presetStatus = document.getElementById('preset-status');
            const initial = window.charmCalculatorInitial ?? { monsters: [], configuration: null };
            const knownMonsters = new Map((initial.monsters ?? []).map((monster) => [monster.slug, monster]));
            let selectedMonsters = (initial.configuration?.monsters ?? []).map((entry) => ({
                monster_slug: entry.monster_slug,
                kills: Number(entry.kills),
            }));
            let searchTimer;
            let inventoryConfirmed = initial.configuration?.inventory_confirmed === true;
            let lastOptimization = null;

            const formatNumber = (value, maximumFractionDigits = 1) => new Intl.NumberFormat('pt-BR', {
                maximumFractionDigits,
            }).format(value);

            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            })[character]);
            const safeExternalUrl = (value) => {
                try {
                    const url = new URL(value);
                    return ['http:', 'https:'].includes(url.protocol) ? url.href : null;
                } catch {
                    return null;
                }
            };

            const selectedCharms = () => [...document.querySelectorAll('[data-charm-owned]:checked')].flatMap((checkbox) => {
                const selectedTier = document.querySelector(`[data-charm-tier="${checkbox.dataset.charmOwned}"][aria-pressed="true"]`);
                return selectedTier ? [{
                    charm_slug: checkbox.dataset.charmOwned,
                    tier: Number(selectedTier.dataset.tier),
                }] : [];
            });

            const currentConfiguration = () => ({
                character: {
                    hp: Number(document.getElementById('character-hp').value),
                    mana: Number(document.getElementById('character-mana').value),
                    account_type: document.getElementById('character-account-type').value,
                },
                monsters: selectedMonsters,
                charms: selectedCharms(),
                criterion: document.getElementById('optimization-criterion').value,
                inventory_confirmed: inventoryConfirmed,
            });

            const setTierButtons = (slug, tier) => {
                document.querySelectorAll(`[data-charm-tier="${slug}"]`).forEach((button) => {
                    const selected = Number(button.dataset.tier) === Number(tier);
                    button.setAttribute('aria-pressed', String(selected));
                });
            };

            const refreshInventoryControls = () => {
                document.querySelectorAll('[data-charm-owned]').forEach((checkbox) => {
                    const enabled = checkbox.checked;
                    const option = checkbox.closest('[data-charm-option]');
                    const status = option.querySelector('[data-charm-status]');
                    status.textContent = enabled ? 'Habilitado' : 'Desabilitado';
                    status.classList.toggle('text-emerald-300', enabled);
                    status.classList.toggle('text-slate-500', !enabled);
                    option.classList.toggle('border-amber-600', enabled);
                    option.classList.toggle('bg-amber-950/10', enabled);
                    option.classList.toggle('opacity-60', !enabled);
                    option.querySelectorAll('[data-charm-tier]').forEach((button) => {
                        const selected = enabled && button.getAttribute('aria-pressed') === 'true';
                        button.classList.toggle('bg-amber-500', selected);
                        button.classList.toggle('text-slate-950', selected);
                        button.classList.toggle('font-bold', selected);
                    });
                });
            };

            const clearOptimization = () => {
                const hadResults = lastOptimization !== null;
                lastOptimization = null;
                if (hadResults) renderMonsters();
                huntPresetBox.replaceChildren();
                huntPresetBox.classList.add('hidden');
                document.getElementById('calculate-charms').disabled = !inventoryConfirmed;
                document.querySelectorAll('#save-charm-preset, #rename-charm-preset').forEach((button) => { button.disabled = true; });
            };

            const invalidateConfirmation = () => {
                inventoryConfirmed = false;
                inventoryStatus.textContent = 'Inventário não confirmado. Confirme novamente após revisar as alterações.';
                inventoryStatus.classList.remove('text-emerald-300');
                inventoryStatus.classList.add('text-amber-300');
                clearOptimization();
            };

            const renderHuntPreset = (charms, capacity, accountType) => {
                if (!charms.length) {
                    huntPresetBox.classList.add('hidden');
                    huntPresetBox.replaceChildren();
                    return;
                }
                const labels = { free: 'Free Account', premium: 'Premium Account', expansion: 'Charm Expansion' };
                huntPresetBox.innerHTML = `
                    <p class="font-semibold">Preset recomendado para a caçada</p>
                    <p class="mt-1">${charms.map((charm) => `${escapeHtml(charm.charm_type)} · ${escapeHtml(charm.charm)} T${charm.tier}`).join(' · ')}</p>
                    <p class="mt-1 text-xs text-emerald-200/70">União deduplicada das recomendações por criatura. Limite de Major aplicado: ${capacity} (${labels[accountType] ?? accountType}).</p>
                `;
                huntPresetBox.classList.remove('hidden');
            };

            const renderMonsters = () => {
                selectedMonstersBox.replaceChildren();
                selectedMonsters.forEach((entry) => {
                    const monster = knownMonsters.get(entry.monster_slug);
                    if (!monster) return;

                    const result = lastOptimization?.results?.find((item) => item.monster.slug === entry.monster_slug);
                    const chips = ['physical', 'earth', 'fire', 'energy', 'ice', 'holy', 'death', 'healing']
                        .map((element) => resistanceChip(element, monster.resistances?.[element])).join('');
                    const sourceUrl = safeExternalUrl(monster.source_url);
                    const imageUrl = safeExternalUrl(monster.image_url);
                    const card = document.createElement('div');
                    card.className = 'relative rounded-xl border border-slate-800 bg-slate-900/60 p-3';
                    card.innerHTML = `
                        <button type="button" data-remove-monster="${escapeHtml(entry.monster_slug)}" aria-label="Remover ${escapeHtml(monster.name)}" class="absolute -left-2 -top-2 flex h-5 w-5 items-center justify-center rounded-full bg-rose-900 text-[10px] text-rose-100 hover:bg-rose-700">✕</button>
                        <div class="grid items-center gap-3 sm:grid-cols-[2.5rem_minmax(0,1fr)_5rem_11rem]">
                            <span class="hidden h-10 w-10 items-center justify-center rounded-lg bg-slate-800 sm:flex" aria-hidden="true">${imageUrl
                                ? `<img src="${escapeHtml(imageUrl)}" alt="" loading="lazy" referrerpolicy="no-referrer" class="max-h-10 max-w-10 object-contain" style="image-rendering:pixelated">`
                                : `<span class="text-lg font-black text-slate-300">${escapeHtml(monster.name.charAt(0))}</span>`}</span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-baseline gap-x-2">
                                    <span class="font-bold text-white">${escapeHtml(monster.name)}</span>
                                    <span class="text-[11px] text-rose-300">❤ ${formatNumber(monster.health, 0)} HP</span>
                                    ${sourceUrl ? `<a class="text-[10px] text-cyan-300 underline" href="${escapeHtml(sourceUrl)}" target="_blank" rel="noreferrer">fonte</a>` : ''}
                                </div>
                                <div class="mt-1 flex flex-wrap gap-1" aria-label="Defesas elementais">${chips}</div>
                            </div>
                            <label class="text-center text-[10px] uppercase tracking-wide text-slate-400">
                                Proporção %
                                <input data-monster-kills="${escapeHtml(entry.monster_slug)}" type="number" min="0" max="1000000" value="${entry.kills}" class="tool-input mt-1 !px-2 !py-1 text-center">
                            </label>
                            ${bestCharmCard(result)}
                        </div>
                        ${alternativesDetails(result)}
                    `;
                    selectedMonstersBox.append(card);
                });
            };

            const addMonster = (monster) => {
                if (selectedMonsters.some((entry) => entry.monster_slug === monster.slug)) return;
                if (selectedMonsters.length >= 20) {
                    calculationStatus.textContent = 'O limite é de 20 criaturas por configuração.';
                    return;
                }

                knownMonsters.set(monster.slug, monster);
                selectedMonsters.push({ monster_slug: monster.slug, kills: monster.spawn_count ?? 100 });
                renderMonsters();
                invalidateConfirmation();
                searchInput.value = '';
                searchResults.replaceChildren();
            };

            const elementMeta = {
                physical: ['/images/elements/physical.png', 'Físico', 'text-slate-200'],
                earth: ['/images/elements/earth.gif', 'Terra', 'text-emerald-300'],
                fire: ['/images/elements/fire.gif', 'Fogo', 'text-orange-300'],
                energy: ['/images/elements/energy.gif', 'Energia', 'text-fuchsia-300'],
                ice: ['/images/elements/ice.gif', 'Gelo', 'text-cyan-300'],
                holy: ['/images/elements/holy.gif', 'Sagrado', 'text-amber-300'],
                death: ['/images/elements/death.gif', 'Morte', 'text-purple-300'],
                healing: ['/images/elements/healing.png', 'Cura', 'text-lime-300'],
            };

            const resistanceChip = (element, multiplier) => {
                const [icon, label] = elementMeta[element];
                const known = typeof multiplier === 'number';
                const tone = !known
                    ? 'border-slate-700 text-slate-500'
                    : multiplier > 1
                        ? 'border-emerald-700 bg-emerald-950/50 text-emerald-300'
                        : multiplier < 1
                            ? 'border-rose-800 bg-rose-950/40 text-rose-300'
                            : 'border-slate-700 bg-slate-900 text-slate-300';
                return `<span title="${label}" class="inline-flex items-center gap-1 rounded-md border px-1.5 py-0.5 text-[11px] font-semibold ${tone}"><span aria-hidden="true" class="inline-flex"><img src="${icon}" alt="" class="max-h-3.5 w-auto [image-rendering:pixelated]"></span>${known ? `${formatNumber(multiplier * 100, 0)}%` : '—'}</span>`;
            };

            const bestCharmCard = (result) => {
                if (!result) {
                    return '<div class="rounded-xl border border-dashed border-slate-700 px-3 py-2 text-center text-[11px] text-slate-500">Aguardando cálculo</div>';
                }
                const combination = result.recommended_combination ?? [];
                if (!combination.length) {
                    return '<div class="rounded-xl border border-dashed border-slate-700 px-3 py-2 text-center text-[11px] text-slate-400">Sem charm disponível: os habilitados já foram usados em outras criaturas ou não têm dano calculável</div>';
                }
                return `<div class="space-y-1">${combination.map((item) => {
                    const [, elementLabel, tone] = elementMeta[item.element] ?? ['🔹', item.charm_type, 'text-slate-300'];
                    const effectiveness = item.resistance_multiplier === undefined ? '' : `Efetividade <b class="text-emerald-300">${formatNumber(item.resistance_multiplier * 100, 0)}%</b> · `;
                    return `
                        <div class="rounded-xl border border-amber-700/60 bg-amber-950/20 px-3 py-1.5 text-center">
                            <div class="flex items-center justify-center gap-1.5 text-xs font-bold text-white"><img src="/images/charms/${item.charm.toLowerCase().replace(/'/g, '').replace(/[^a-z0-9]+/g, '-')}.gif" alt="" width="28" height="28" class="h-7 w-7 object-contain [image-rendering:pixelated]"> <span>${escapeHtml(item.charm)} (T${item.tier})</span></div>
                            <div class="text-[10px] ${tone}">${escapeHtml(elementLabel)}</div>
                            <div class="text-sm font-black text-white">${formatNumber(item.damage_per_activation ?? 0, 0)} <span class="text-[10px] font-normal text-slate-400">dano</span></div>
                            <div class="text-[10px] text-slate-400">${effectiveness}Chance: ${formatNumber((item.activation_chance ?? 0) * 100, 0)}%</div>
                        </div>`;
                }).join('')}</div>`;
            };

            const alternativesDetails = (result) => {
                if (!result) return '';
                const list = (result.alternatives ?? []).map((alternative) => `
                    <li class="flex flex-wrap justify-between gap-2 border-t border-slate-800 py-1">
                        <span>${escapeHtml(alternative.charm_type)} · ${escapeHtml(alternative.charm)} T${alternative.tier}${alternative.cost ? ` · ${formatNumber(alternative.cost, 0)} ${alternative.cost_currency === 'charm_points' ? 'CP' : 'Echoes'}` : ''}</span>
                        <span class="text-slate-400">${alternative.calculable
                            ? `${formatNumber(alternative.expected_damage_per_eligible_attack)} esperado/ataque elegível`
                            : `${escapeHtml(alternative.limitation ?? alternative.effect_summary)}`}</span>
                    </li>
                `).join('');
                return `<details class="mt-2 text-[11px] text-slate-300"><summary class="cursor-pointer text-slate-400">Comparar todos os charms habilitados</summary><ul class="mt-1">${list || '<li class="text-slate-500">Nenhum charm habilitado.</li>'}</ul></details>`;
            };

            const calculate = async () => {
                errorBox.classList.add('hidden');
                if (!inventoryConfirmed) {
                    calculationStatus.textContent = 'Confirme os charms habilitados antes de calcular.';
                    return;
                }
                if (!selectedMonsters.length) {
                    calculationStatus.textContent = 'Adicione ao menos uma criatura para calcular.';
                    return;
                }

                calculationStatus.textContent = 'Calculando no servidor…';
                try {
                    const response = await fetch(charmOptimizer.dataset.calculateUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(currentConfiguration()),
                    });
                    const data = await response.json();
                    if (!response.ok) {
                        const firstError = Object.values(data.errors ?? {})[0]?.[0];
                        throw new Error(firstError || data.message || 'Não foi possível calcular esta configuração.');
                    }

                    lastOptimization = data;
                    renderMonsters();
                    renderHuntPreset(data.hunt_preset, data.major_charm_capacity, data.account_type);
                    calculationStatus.textContent = data.limitation;
                    document.querySelectorAll('#save-charm-preset, #rename-charm-preset').forEach((button) => { button.disabled = false; });
                } catch (error) {
                    lastOptimization = null;
                    errorBox.textContent = error.message;
                    errorBox.classList.remove('hidden');
                    calculationStatus.textContent = 'Revise os parâmetros e tente novamente.';
                }
            };

            const searchCreatures = async () => {
                const query = searchInput.value.trim();
                searchResults.replaceChildren();
                if (query.length < 2) return;

                try {
                    const url = new URL(charmOptimizer.dataset.creaturesUrl, window.location.origin);
                    url.searchParams.set('q', query);
                    const response = await fetch(url, { headers: { Accept: 'application/json' } });
                    if (!response.ok) throw new Error('Não foi possível pesquisar criaturas.');
                    const matches = await response.json();
                    matches.forEach((monster) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'flex w-full items-center justify-between rounded-lg border border-slate-800 bg-slate-900 px-3 py-2 text-left text-sm text-slate-200 hover:border-violet-500';
                        button.innerHTML = `<span>${escapeHtml(monster.name)}</span><span class="text-xs text-slate-400">${formatNumber(monster.health, 0)} HP · Adicionar</span>`;
                        button.addEventListener('click', () => addMonster(monster));
                        searchResults.append(button);
                    });
                    if (!matches.length) searchResults.innerHTML = '<p class="text-xs text-slate-500">Nenhuma criatura encontrada no catálogo local.</p>';
                } catch (error) {
                    calculationStatus.textContent = error.message;
                }
            };

            const filterCharmOptions = () => {
                const category = document.getElementById('charm-category-filter').value;
                const query = charmSearchInput.value.trim().toLocaleLowerCase();
                document.querySelectorAll('[data-charm-option]').forEach((option) => {
                    const matchesCategory = !category || option.dataset.category === category;
                    const matchesName = !query || option.dataset.name.includes(query);
                    option.classList.toggle('hidden', !matchesCategory || !matchesName);
                });
            };

            const presetRequest = async (url, method, body) => {
                const response = await fetch(url, {
                    method,
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    ...(body ? { body: JSON.stringify(body) } : {}),
                });
                const data = await response.json();
                if (!response.ok) {
                    const firstError = Object.values(data.errors ?? {})[0]?.[0];
                    throw new Error(firstError || data.message || 'A operação do preset falhou.');
                }
                return data;
            };

            const setConfiguration = async (configuration) => {
                document.getElementById('character-hp').value = configuration.character.hp;
                document.getElementById('character-mana').value = configuration.character.mana;
                document.getElementById('character-account-type').value = configuration.character.account_type ?? 'premium';
                document.getElementById('optimization-criterion').value = configuration.criterion ?? 'individual';
                selectedMonsters = configuration.monsters.map((entry) => ({
                    monster_slug: entry.monster_slug,
                    kills: Number(entry.kills),
                }));
                configuration.monsters.forEach((entry) => {
                    const monster = initial.monsters.find((item) => item.slug === entry.monster_slug);
                    if (monster) knownMonsters.set(monster.slug, monster);
                });
                const missingSlugs = selectedMonsters
                    .map((entry) => entry.monster_slug)
                    .filter((slug) => !knownMonsters.has(slug));
                if (missingSlugs.length) {
                    const url = new URL(charmOptimizer.dataset.creaturesUrl, window.location.origin);
                    missingSlugs.forEach((slug) => url.searchParams.append('slugs[]', slug));
                    const response = await fetch(url, { headers: { Accept: 'application/json' } });
                    if (!response.ok) throw new Error('Não foi possível carregar as criaturas deste preset.');
                    (await response.json()).forEach((monster) => knownMonsters.set(monster.slug, monster));
                }
                document.querySelectorAll('[data-charm-owned]').forEach((checkbox) => {
                    const selected = (configuration.charms ?? []).find((entry) => entry.charm_slug === checkbox.dataset.charmOwned);
                    checkbox.checked = Boolean(selected);
                    document.querySelectorAll(`[data-charm-tier="${checkbox.dataset.charmOwned}"]`).forEach((button) => {
                        button.setAttribute('aria-pressed', String(Boolean(selected) && Number(button.dataset.tier) === Number(selected.tier)));
                    });
                });
                refreshInventoryControls();
                renderMonsters();
                inventoryConfirmed = configuration.inventory_confirmed === true;
                inventoryStatus.textContent = inventoryConfirmed
                    ? 'Inventário confirmado. Uma alteração em qualquer parâmetro exigirá nova confirmação.'
                    : 'Confirme o inventário antes de calcular. Qualquer alteração invalida a confirmação.';
                inventoryStatus.classList.toggle('text-emerald-300', inventoryConfirmed);
                inventoryStatus.classList.toggle('text-amber-300', !inventoryConfirmed);
                clearOptimization();
            };

            searchInput.addEventListener('input', () => {
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(searchCreatures, 200);
            });
            const huntImport = document.getElementById('hunt-import');
            const importHunt = async (slug) => {
                if (!slug) return;
                calculationStatus.textContent = 'Importando criaturas da hunt...';
                try {
                    const response = await fetch(`${huntImport.dataset.huntUrl}/${encodeURIComponent(slug)}/creatures`, { headers: { Accept: 'application/json' } });
                    if (!response.ok) throw new Error('Não foi possível importar as criaturas desta hunt.');
                    const { monsters, missing } = await response.json();
                    const before = selectedMonsters.length;
                    const weighted = monsters.some((monster) => monster.spawn_count);
                    monsters.map((monster) => (weighted ? { ...monster, spawn_count: monster.spawn_count ?? 1 } : monster)).forEach(addMonster);
                    if (weighted) {
                        selectedMonsters.sort((a, b) => b.kills - a.kills);
                        document.getElementById('optimization-criterion').value = 'hunt';
                        renderMonsters();
                    }
                    const added = selectedMonsters.length - before;
                    const huntName = huntImport.querySelector(`option[value="${CSS.escape(slug)}"]`)?.textContent.trim() ?? slug;
                    calculationStatus.textContent = `${added} criatura(s) importada(s) de ${huntName}.`
                        + (missing.length ? ` Sem dados no catálogo: ${missing.join(', ')}.` : '');
                } catch (error) {
                    calculationStatus.textContent = error.message;
                } finally {
                    huntImport.value = '';
                }
            };
            huntImport.addEventListener('change', () => importHunt(huntImport.value));
            const requestedHunt = new URLSearchParams(window.location.search).get('hunt');
            if (requestedHunt && !initial.configuration) importHunt(requestedHunt);
            charmSearchInput.addEventListener('input', filterCharmOptions);
            document.getElementById('charm-category-filter').addEventListener('change', (event) => {
                filterCharmOptions();
            });
            selectedMonstersBox.addEventListener('click', (event) => {
                const button = event.target.closest('[data-remove-monster]');
                if (!button) return;
                selectedMonsters = selectedMonsters.filter((entry) => entry.monster_slug !== button.dataset.removeMonster);
                renderMonsters();
                invalidateConfirmation();
            });
            selectedMonstersBox.addEventListener('input', (event) => {
                const input = event.target.closest('[data-monster-kills]');
                if (!input) return;
                const entry = selectedMonsters.find((monster) => monster.monster_slug === input.dataset.monsterKills);
                if (entry) {
                    entry.kills = Number(input.value);
                    invalidateConfirmation();
                }
            });
            document.getElementById('confirm-charm-inventory').addEventListener('click', () => {
                const enabledWithoutTier = [...document.querySelectorAll('[data-charm-owned]:checked')]
                    .some((checkbox) => !document.querySelector(`[data-charm-tier="${checkbox.dataset.charmOwned}"][aria-pressed="true"]`));
                if (enabledWithoutTier) {
                    inventoryStatus.textContent = 'Escolha o tier possuído para cada charm habilitado antes de confirmar.';
                    inventoryStatus.classList.remove('text-emerald-300');
                    inventoryStatus.classList.add('text-amber-300');
                    return;
                }
                inventoryConfirmed = true;
                inventoryStatus.textContent = 'Inventário confirmado. Qualquer alteração em charms, tier, personagem, criaturas ou critério exigirá nova confirmação.';
                inventoryStatus.classList.remove('text-amber-300');
                inventoryStatus.classList.add('text-emerald-300');
                clearOptimization();
            });
            document.getElementById('charm-options').addEventListener('click', (event) => {
                const option = event.target.closest('[data-charm-option]');
                if (!option) return;
                const checkbox = option.querySelector('[data-charm-owned]');
                if (checkbox.disabled) return;
                const button = event.target.closest('[data-charm-tier]');
                if (button) {
                    checkbox.checked = true;
                    setTierButtons(button.dataset.charmTier, button.dataset.tier);
                } else {
                    checkbox.checked = !checkbox.checked;
                }
                refreshInventoryControls();
                invalidateConfirmation();
            });
            [
                document.getElementById('character-hp'),
                document.getElementById('character-mana'),
                document.getElementById('character-account-type'),
                document.getElementById('optimization-criterion'),
            ].forEach((input) => input.addEventListener('input', invalidateConfirmation));
            document.getElementById('character-account-type').addEventListener('change', invalidateConfirmation);
            document.getElementById('optimization-criterion').addEventListener('change', invalidateConfirmation);
            document.getElementById('calculate-charms').addEventListener('click', calculate);
            document.getElementById('reset-charm-config').addEventListener('click', () => {
                if (!window.confirm('Limpar a configuração atual?')) return;
                document.getElementById('character-hp').value = 1000;
                document.getElementById('character-mana').value = 1000;
                document.getElementById('character-account-type').value = 'premium';
                document.getElementById('optimization-criterion').value = 'individual';
                document.querySelectorAll('[data-charm-owned]').forEach((checkbox) => { checkbox.checked = false; });
                document.querySelectorAll('[data-charm-tier]').forEach((button) => button.setAttribute('aria-pressed', 'false'));
                refreshInventoryControls();
                selectedMonsters = [];
                renderMonsters();
                invalidateConfirmation();
            });

            const presetSelect = document.getElementById('saved-charm-presets');
            if (presetSelect) {
                const presetUrl = charmOptimizer.dataset.presetsUrl.replace(/\/$/, '');
                const presetName = document.getElementById('preset-name');
                const selectedPresetUrl = () => `${presetUrl}/${encodeURIComponent(presetSelect.value)}`;
                const reportPresetError = (error) => { presetStatus.textContent = error.message; };

                document.getElementById('save-charm-preset').addEventListener('click', async () => {
                        if (!lastOptimization || !inventoryConfirmed) {
                            presetStatus.textContent = 'Confirme o inventário e calcule as recomendações antes de salvar.';
                            return;
                        }
                        try {
                        const data = await presetRequest(presetUrl, 'POST', {
                            name: presetName.value,
                            configuration: currentConfiguration(),
                        });
                        const option = new Option(data.preset.name, data.preset.id, true, true);
                        presetSelect.add(option);
                        presetStatus.textContent = 'Preset salvo.';
                    } catch (error) { reportPresetError(error); }
                });
                document.getElementById('load-charm-preset').addEventListener('click', async () => {
                    if (!presetSelect.value) return;
                    try {
                        const data = await presetRequest(selectedPresetUrl(), 'GET');
                        presetName.value = data.preset.name;
                        await setConfiguration(data.preset.configuration);
                        presetStatus.textContent = 'Preset carregado.';
                    } catch (error) { reportPresetError(error); }
                });
                document.getElementById('rename-charm-preset').addEventListener('click', async () => {
                    if (!lastOptimization || !inventoryConfirmed) {
                        presetStatus.textContent = 'Confirme o inventário e calcule as recomendações antes de atualizar.';
                        return;
                    }
                    if (!presetSelect.value) {
                        presetStatus.textContent = 'Selecione um preset para atualizar.';
                        return;
                    }
                    try {
                        const data = await presetRequest(selectedPresetUrl(), 'PUT', {
                            name: presetName.value,
                            configuration: currentConfiguration(),
                        });
                        presetSelect.selectedOptions[0].textContent = data.preset.name;
                        presetStatus.textContent = 'Preset atualizado.';
                    } catch (error) { reportPresetError(error); }
                });
                document.getElementById('delete-charm-preset').addEventListener('click', async () => {
                    if (!presetSelect.value || !window.confirm('Excluir este preset?')) return;
                    try {
                        await presetRequest(selectedPresetUrl(), 'DELETE');
                        presetSelect.selectedOptions[0].remove();
                        presetName.value = '';
                        presetStatus.textContent = 'Preset excluído.';
                    } catch (error) { reportPresetError(error); }
                });
                document.getElementById('share-charm-preset').addEventListener('click', async () => {
                    if (!presetSelect.value) {
                        presetStatus.textContent = 'Selecione um preset para compartilhar.';
                        return;
                    }
                    try {
                        const data = await presetRequest(`${selectedPresetUrl()}/share`, 'POST');
                        try {
                            await navigator.clipboard.writeText(data.url);
                            presetStatus.textContent = `Link copiado: ${data.url}`;
                        } catch {
                            presetStatus.textContent = `Link público (copie manualmente): ${data.url}`;
                        }
                    } catch (error) { reportPresetError(error); }
                });
                document.getElementById('revoke-charm-share').addEventListener('click', async () => {
                    if (!presetSelect.value) {
                        presetStatus.textContent = 'Selecione um preset para revogar o link.';
                        return;
                    }
                    try {
                        await presetRequest(`${selectedPresetUrl()}/share`, 'DELETE');
                        presetStatus.textContent = 'Link de compartilhamento revogado.';
                    } catch (error) { reportPresetError(error); }
                });
                presetSelect.addEventListener('change', () => {
                    const option = presetSelect.selectedOptions[0];
                    if (option?.value) presetName.value = option.textContent;
                });
            }

            renderMonsters();
            refreshInventoryControls();
            filterCharmOptions();
            clearOptimization();
            if (initial.configuration) {
                setConfiguration(initial.configuration)
                    .then(() => initial.configuration.inventory_confirmed === true ? calculate() : null)
                    .catch((error) => {
                        errorBox.textContent = error.message;
                        errorBox.classList.remove('hidden');
                    });
            }
        }
