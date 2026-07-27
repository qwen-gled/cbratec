import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import api from '../../services/api';
import './Dashboard.css';

const Dashboard = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [stats, setStats] = useState(null);
  const [abstracts, setAbstracts] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadDashboardData();
  }, []);

  const loadDashboardData = async () => {
    try {
      setLoading(true);
      
      // Carregar estatísticas (apenas para admin/moderador)
      if (user?.role === 'admin' || user?.role === 'moderator') {
        try {
          const statsResponse = await api.get('/admin/stats');
          setStats(statsResponse.data);
        } catch (error) {
          console.error('Erro ao carregar estatísticas:', error);
        }
      }

      // Carregar resumos do usuário
      try {
        const abstractsResponse = await api.get('/abstracts', {
          params: { status: 'all' }
        });
        setAbstracts(abstractsResponse.data.abstracts || []);
      } catch (error) {
        console.error('Erro ao carregar resumos:', error);
      }
    } catch (error) {
      console.error('Erro ao carregar dados do dashboard:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  const getStatusBadge = (status) => {
    const statusMap = {
      pending: { label: 'Pendente', class: 'badge-pending' },
      approved: { label: 'Aprovado', class: 'badge-approved' },
      rejected: { label: 'Rejeitado', class: 'badge-rejected' },
      under_review: { label: 'Em Análise', class: 'badge-review' },
    };
    const config = statusMap[status] || { label: status, class: 'badge-default' };
    return <span className={`badge ${config.class}`}>{config.label}</span>;
  };

  if (loading) {
    return (
      <div className="dashboard-container">
        <div className="loading">Carregando...</div>
      </div>
    );
  }

  return (
    <div className="dashboard-container">
      <header className="dashboard-header">
        <div className="header-content">
          <h1>Dashboard</h1>
          <div className="user-info">
            <span className="user-name">{user?.name}</span>
            <span className="user-role">{user?.role === 'admin' ? 'Administrador' : user?.role === 'moderator' ? 'Moderador' : 'Usuário'}</span>
            <button onClick={handleLogout} className="btn-logout">
              Sair
            </button>
          </div>
        </div>
      </header>

      <main className="dashboard-main">
        {/* Estatísticas para Admin/Moderador */}
        {(user?.role === 'admin' || user?.role === 'moderator') && stats && (
          <section className="stats-section">
            <h2>Estatísticas</h2>
            <div className="stats-grid">
              <div className="stat-card">
                <div className="stat-value">{stats.totalAbstracts || 0}</div>
                <div className="stat-label">Total de Resumos</div>
              </div>
              <div className="stat-card">
                <div className="stat-value">{stats.pendingAbstracts || 0}</div>
                <div className="stat-label">Pendentes</div>
              </div>
              <div className="stat-card">
                <div className="stat-value">{stats.approvedAbstracts || 0}</div>
                <div className="stat-label">Aprovados</div>
              </div>
              <div className="stat-card">
                <div className="stat-value">{stats.totalUsers || 0}</div>
                <div className="stat-label">Usuários</div>
              </div>
            </div>
          </section>
        )}

        {/* Lista de Resumos */}
        <section className="abstracts-section">
          <div className="section-header">
            <h2>Meus Resumos</h2>
            <button 
              onClick={() => navigate('/abstracts/new')} 
              className="btn-new"
            >
              + Novo Resumo
            </button>
          </div>

          {abstracts.length === 0 ? (
            <div className="empty-state">
              <p>Você ainda não submeteu nenhum resumo.</p>
              <button 
                onClick={() => navigate('/abstracts/new')} 
                className="btn-primary"
              >
                Submeter Primeiro Resumo
              </button>
            </div>
          ) : (
            <div className="abstracts-list">
              <table className="abstracts-table">
                <thead>
                  <tr>
                    <th>Título</th>
                    <th>Área</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Ações</th>
                  </tr>
                </thead>
                <tbody>
                  {abstracts.map((abstract) => (
                    <tr key={abstract.id}>
                      <td className="title-cell">{abstract.title}</td>
                      <td>{abstract.area}</td>
                      <td>{getStatusBadge(abstract.status)}</td>
                      <td>{new Date(abstract.createdAt).toLocaleDateString('pt-BR')}</td>
                      <td>
                        <button 
                          onClick={() => navigate(`/abstracts/${abstract.id}`)}
                          className="btn-action"
                        >
                          Ver
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </section>

        {/* Atalhos Rápidos para Admin */}
        {user?.role === 'admin' && (
          <section className="quick-links-section">
            <h2>Acesso Rápido</h2>
            <div className="quick-links-grid">
              <button 
                onClick={() => navigate('/admin/users')}
                className="quick-link-card"
              >
                <h3>👥 Usuários</h3>
                <p>Gerenciar usuários</p>
              </button>
              <button 
                onClick={() => navigate('/admin/payments')}
                className="quick-link-card"
              >
                <h3>💳 Pagamentos</h3>
                <p>Aprovar pagamentos</p>
              </button>
              <button 
                onClick={() => navigate('/admin/areas')}
                className="quick-link-card"
              >
                <h3>📚 Áreas</h3>
                <p>Gerenciar áreas</p>
              </button>
              <button 
                onClick={() => navigate('/admin/settings')}
                className="quick-link-card"
              >
                <h3>⚙️ Configurações</h3>
                <p>Configurar sistema</p>
              </button>
            </div>
          </section>
        )}
      </main>
    </div>
  );
};

export default Dashboard;
