// Dashboard-specific functionality
document.addEventListener('DOMContentLoaded', async () => {
    if (!isAuthenticated()) {
        redirectToLogin();
        return;
    }
    
    const user = getUserInfo();
    if (user && document.getElementById('userName')) {
        document.getElementById('userName').textContent = user.name || user.full_name || user.email;
    }
    
    await loadDashboardData();
    setupAbstractForm();
});

async function loadDashboardData() {
    const token = getToken();
    const user = getUserInfo();
    
    try {
        await loadUserInfo(token);
        await loadAreas(token);
        
        const response = await fetch(`${API_BASE_URL}/abstracts`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });

        if (response.ok) {
            const data = await response.json();
            updateDashboardStats(data, user);
            renderAbstractsList(data);
        } else if (response.status === 401) {
            clearAuth();
            redirectToLogin();
        }
    } catch (error) {
        console.error('Erro ao carregar dashboard:', error);
        document.getElementById('abstractsList').innerHTML = 
            '<p style="color: #e74c3c;">Erro ao carregar dados.</p>';
    }
}

async function loadUserInfo(token) {
    try {
        const response = await fetch(`${API_BASE_URL}/auth/me`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        if (response.ok) {
            const data = await response.json();
            setUserInfo(data.data || data);
        }
    } catch (error) {
        console.error('Erro ao carregar usuário:', error);
    }
}

async function loadAreas(token) {
    try {
        const response = await fetch(`${API_BASE_URL}/admin/areas`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        if (response.ok) {
            const data = await response.json();
            populateAreasSelect(data.data || data);
        }
    } catch (error) {
        console.error('Erro ao carregar áreas:', error);
    }
}

function updatePaymentStatusDisplay(user) {
    const paymentStatusEl = document.getElementById('paymentStatus');
    const paymentAlertEl = document.getElementById('paymentAlert');
    const submitSectionEl = document.getElementById('submitSection');
    
    if (!paymentStatusEl) return;
    
    const paymentStatus = user.payment_status || 'pending';
    const statusLabels = { 'pending': 'Pendente', 'approved': 'Aprovado', 'rejected': 'Rejeitado' };
    
    paymentStatusEl.textContent = statusLabels[paymentStatus] || paymentStatus;
    
    if (paymentAlertEl) {
        if (paymentStatus === 'pending') {
            paymentAlertEl.style.display = 'block';
            paymentAlertEl.className = 'alert alert-warning';
            paymentAlertEl.innerHTML = '<strong>Atenção!</strong> Seu pagamento está pendente.';
        } else if (paymentStatus === 'rejected') {
            paymentAlertEl.style.display = 'block';
            paymentAlertEl.className = 'alert alert-error';
            paymentAlertEl.innerHTML = '<strong>Atenção!</strong> Pagamento rejeitado.';
        } else {
            paymentAlertEl.style.display = 'none';
        }
    }
    
    if (submitSectionEl) {
        submitSectionEl.style.display = (paymentStatus === 'approved') ? 'block' : 'none';
    }
}

function populateAreasSelect(areas) {
    const selectEl = document.getElementById('area_id');
    if (!selectEl) return;
    
    selectEl.innerHTML = '<option value="">Selecione uma área</option>';
    if (Array.isArray(areas)) {
        areas.forEach(area => {
            if (area.is_active !== false) {
                const option = document.createElement('option');
                option.value = area.id;
                option.textContent = area.name;
                selectEl.appendChild(option);
            }
        });
    }
}

function updateDashboardStats(abstracts, user) {
    const total = abstracts.length || 0;
    const pending = abstracts.filter(a => ['pending', 'pending_revision'].includes(a.status)).length;
    const approved = abstracts.filter(a => a.status === 'approved').length;
    
    if (document.getElementById('totalAbstracts')) document.getElementById('totalAbstracts').textContent = total;
    if (document.getElementById('pendingAbstracts')) document.getElementById('pendingAbstracts').textContent = pending;
    if (document.getElementById('approvedAbstracts')) document.getElementById('approvedAbstracts').textContent = approved;
    
    updatePaymentStatusDisplay(user);
    checkSubmitAvailability(abstracts, user);
}

function checkSubmitAvailability(abstracts, user) {
    const submitSectionEl = document.getElementById('submitSection');
    const formMessageEl = document.getElementById('formMessage');
    
    if (!submitSectionEl || user.payment_status !== 'approved') return;
    
    const activeAbstracts = abstracts.filter(a => a.status !== 'rejected');
    const maxAllowed = 2;
    
    if (activeAbstracts.length >= maxAllowed) {
        if (formMessageEl) {
            formMessageEl.textContent = `Limite de ${maxAllowed} resumos atingido. Rejeitados não contam.`;
            formMessageEl.className = 'message error';
            formMessageEl.style.display = 'block';
        }
        disableForm(true);
    } else {
        if (formMessageEl) formMessageEl.style.display = 'none';
        disableForm(false);
    }
}

function disableForm(disabled) {
    const formEl = document.getElementById('abstractForm');
    if (!formEl) return;
    formEl.querySelectorAll('input, select, button[type="submit"]').forEach(input => input.disabled = disabled);
}

function setupAbstractForm() {
    const formEl = document.getElementById('abstractForm');
    if (!formEl) return;
    
    formEl.addEventListener('submit', async (e) => {
        e.preventDefault();
        const token = getToken();
        const formData = new FormData(formEl);
        const messageEl = document.getElementById('formMessage');
        
        if (messageEl) { messageEl.textContent = ''; messageEl.className = 'message'; }
        
        try {
            const response = await fetch(`${API_BASE_URL}/abstracts`, {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token}` },
                body: formData
            });
            
            const data = await response.json();
            
            if (response.ok) {
                if (messageEl) {
                    messageEl.textContent = 'Resumo enviado com sucesso!';
                    messageEl.className = 'message success';
                    messageEl.style.display = 'block';
                }
                formEl.reset();
                await loadDashboardData();
            } else {
                if (messageEl) {
                    messageEl.textContent = data.error || 'Erro ao enviar resumo';
                    messageEl.className = 'message error';
                    messageEl.style.display = 'block';
                }
            }
        } catch (error) {
            console.error('Erro:', error);
            if (messageEl) {
                messageEl.textContent = 'Erro de conexão.';
                messageEl.className = 'message error';
                messageEl.style.display = 'block';
            }
        }
    });
}

function renderAbstractsList(abstracts) {
    const listEl = document.getElementById('abstractsList');
    
    if (!abstracts || abstracts.length === 0) {
        listEl.innerHTML = '<p>Nenhum resumo encontrado.</p>';
        return;
    }
    
    const statusLabels = {
        'pending': 'Em Análise',
        'pending_revision': 'Pendente de Revisão',
        'approved': 'Aprovado',
        'rejected': 'Rejeitado',
        'accepted_with_corrections': 'Aceito com Correções'
    };
    
    const replaceableStatuses = ['pending', 'pending_revision', 'accepted_with_corrections'];
    
    let html = '<table class="abstracts-table"><thead><tr><th>Título</th><th>Área</th><th>Status</th><th>Histórico</th><th>Ações</th></tr></thead><tbody>';
    
    abstracts.forEach(abstract => {
        html += `<tr>
            <td>${escapeHtml(abstract.title || 'Sem título')}</td>
            <td>${escapeHtml(abstract.area_name || '-')}</td>
            <td><span class="abstract-status status-${abstract.status}">${statusLabels[abstract.status] || abstract.status}</span></td>
            <td><button class="btn-sm btn-info" onclick="viewHistory(${abstract.id})">Ver Histórico</button></td>
            <td>${replaceableStatuses.includes(abstract.status) 
                ? `<label class="btn-sm btn-warning" style="display:inline-block;cursor:pointer;">Substituir<input type="file" accept=".pdf" style="display:none;" onchange="replaceFile(${abstract.id},this)"></label>`
                : `<button class="btn-sm btn-disabled" disabled>${abstract.status === 'rejected' ? 'Não substituível' : 'Bloqueado'}</button>`
            }</td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    listEl.innerHTML = html;
}

async function viewHistory(abstractId) {
    const token = getToken();
    const modalEl = document.getElementById('historyModal');
    const contentEl = document.getElementById('historyContent');
    
    if (!modalEl || !contentEl) return;
    
    try {
        const response = await fetch(`${API_BASE_URL}/abstracts/${abstractId}`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (response.ok) {
            const data = await response.json();
            const abstract = data.data || data;
            
            const statusLabels = {
                'pending': 'Em Análise', 'pending_revision': 'Pendente de Revisão',
                'approved': 'Aprovado', 'rejected': 'Rejeitado', 'accepted_with_corrections': 'Aceito com Correções'
            };
            
            let historyHtml = `<p><strong>Resumo:</strong> ${escapeHtml(abstract.title)}</p>`;
            historyHtml += `<p><strong>Status Atual:</strong> <span class="abstract-status status-${abstract.status}">${statusLabels[abstract.status] || abstract.status}</span></p>`;
            historyHtml += '<h3>Linha do Tempo</h3><ul class="history-timeline">';
            
            if (abstract.history && abstract.history.length > 0) {
                abstract.history.forEach(item => {
                    const date = new Date(item.changed_at).toLocaleString('pt-BR');
                    historyHtml += `<li class="history-item">
                        <div class="history-status">${item.previous_status ? `De: ${statusLabels[item.previous_status] || item.previous_status} → ` : ''}Para: ${statusLabels[item.new_status] || item.new_status}</div>
                        <div class="history-date">${date}</div>
                        ${item.justification ? `<div class="history-justification"><strong>Justificativa:</strong> ${escapeHtml(item.justification)}</div>` : ''}
                        <div class="history-user">Por: ${escapeHtml(item.changed_by_name || item.changed_by_email)}</div>
                    </li>`;
                });
            } else {
                historyHtml += '<li class="history-item">Nenhum histórico disponível</li>';
            }
            
            historyHtml += '</ul>';
            contentEl.innerHTML = historyHtml;
            modalEl.style.display = 'flex';
        }
    } catch (error) {
        console.error('Erro ao carregar histórico:', error);
        contentEl.innerHTML = '<p style="color:#e74c3c;">Erro ao carregar histórico</p>';
        modalEl.style.display = 'flex';
    }
}

function closeHistoryModal() {
    const modalEl = document.getElementById('historyModal');
    if (modalEl) modalEl.style.display = 'none';
}

window.onclick = function(event) {
    const modalEl = document.getElementById('historyModal');
    if (event.target === modalEl) modalEl.style.display = 'none';
};

async function replaceFile(abstractId, inputElement) {
    const file = inputElement.files[0];
    if (!file) return;
    
    if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
        alert('Selecione um arquivo PDF.');
        inputElement.value = '';
        return;
    }
    
    const token = getToken();
    const formData = new FormData();
    formData.append('file', file);
    
    try {
        const response = await fetch(`${API_BASE_URL}/abstracts/${abstractId}/replace`, {
            method: 'PUT',
            headers: { 'Authorization': `Bearer ${token}` },
            body: formData
        });
        
        const data = await response.json();
        
        if (response.ok) {
            alert('Arquivo substituído com sucesso!');
            await loadDashboardData();
        } else {
            alert(data.error || 'Erro ao substituir arquivo');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro de conexão.');
    }
    
    inputElement.value = '';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
