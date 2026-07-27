// Dashboard-specific functionality
document.addEventListener('DOMContentLoaded', async () => {
    // Verifica se está autenticado
    if (!isAuthenticated()) {
        redirectToLogin();
        return;
    }

    // Carrega informações do usuário
    const user = getUserInfo();
    if (user && document.getElementById('userName')) {
        document.getElementById('userName').textContent = user.name || user.email;
    }

    // Carrega dados do dashboard
    await loadDashboardData();
});

async function loadDashboardData() {
    const token = getToken();
    
    try {
        // Busca resumos do usuário
        const response = await fetch(`${API_BASE_URL}/abstracts`, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });

        if (response.ok) {
            const data = await response.json();
            updateDashboardStats(data);
            renderAbstractsList(data);
        } else if (response.status === 401) {
            clearAuth();
            redirectToLogin();
        }
    } catch (error) {
        console.error('Erro ao carregar dashboard:', error);
        document.getElementById('abstractsList').innerHTML = 
            '<p style="color: #e74c3c;">Erro ao carregar dados. Verifique se a API está rodando.</p>';
    }
}

function updateDashboardStats(abstracts) {
    const total = abstracts.length || 0;
    const pending = abstracts.filter(a => a.status === 'pending').length;
    const approved = abstracts.filter(a => a.status === 'approved').length;
    
    // Atualiza estatísticas
    if (document.getElementById('totalAbstracts')) {
        document.getElementById('totalAbstracts').textContent = total;
    }
    if (document.getElementById('pendingAbstracts')) {
        document.getElementById('pendingAbstracts').textContent = pending;
    }
    if (document.getElementById('approvedAbstracts')) {
        document.getElementById('approvedAbstracts').textContent = approved;
    }
    
    // Status do pagamento (simulado - deveria vir da API)
    if (document.getElementById('paymentStatus')) {
        document.getElementById('paymentStatus').textContent = pending > 0 ? 'Pendente' : 'OK';
    }
}

function renderAbstractsList(abstracts) {
    const listEl = document.getElementById('abstractsList');
    
    if (!abstracts || abstracts.length === 0) {
        listEl.innerHTML = '<p>Nenhum resumo encontrado. Comece submetendo um novo resumo!</p>';
        return;
    }

    const statusLabels = {
        'pending': 'Em Análise',
        'approved': 'Aprovado',
        'rejected': 'Rejeitado'
    };

    const html = abstracts.map(abstract => `
        <div class="abstract-item">
            <span class="abstract-title">${escapeHtml(abstract.title || 'Sem título')}</span>
            <span class="abstract-status status-${abstract.status}">
                ${statusLabels[abstract.status] || abstract.status}
            </span>
        </div>
    `).join('');

    listEl.innerHTML = html;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
