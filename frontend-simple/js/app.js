// Configuração da API - Altere conforme necessário
const API_BASE_URL = 'http://localhost:3000';

// Funções de utilidade
function showMessage(elementId, message, type) {
    const messageEl = document.getElementById(elementId);
    if (messageEl) {
        messageEl.textContent = message;
        messageEl.className = `message ${type}`;
        // Remove a mensagem após 5 segundos se houver conteúdo
        if (message) {
            setTimeout(() => {
                messageEl.textContent = '';
                messageEl.className = 'message';
            }, 5000);
        }
    }
}

function getToken() {
    return localStorage.getItem('accessToken');
}

function setToken(accessToken, refreshToken) {
    localStorage.setItem('accessToken', accessToken);
    if (refreshToken) {
        localStorage.setItem('refreshToken', refreshToken);
    }
}

function getUserInfo() {
    const userInfo = localStorage.getItem('userInfo');
    return userInfo ? JSON.parse(userInfo) : null;
}

function setUserInfo(user) {
    localStorage.setItem('userInfo', JSON.stringify(user));
}

function clearAuth() {
    localStorage.removeItem('accessToken');
    localStorage.removeItem('refreshToken');
    localStorage.removeItem('userInfo');
}

function isAuthenticated() {
    return !!getToken();
}

function redirectToLogin() {
    window.location.href = 'index.html';
}

// Login Form Handler
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        try {
            const response = await fetch(`${API_BASE_URL}/auth/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();

            if (response.ok) {
                // API retorna access_token e refresh_token
                const accessToken = data.access_token;
                const refreshToken = data.refresh_token;
                
                if (!accessToken) {
                    showMessage('message', 'Erro: Token não recebido da API.', 'error');
                    return;
                }
                
                // Salvar tokens imediatamente
                setToken(accessToken, refreshToken);
                
                // Buscar dados completos do usuário via endpoint /auth/me
                // Usar o token já salvo para garantir consistência
                try {
                    const meResponse = await fetch(`${API_BASE_URL}/auth/me`, {
                        method: 'GET',
                        headers: {
                            'Authorization': `Bearer ${accessToken}`,
                            'Content-Type': 'application/json'
                        }
                    });
                    
                    if (meResponse.ok) {
                        const userData = await meResponse.json();
                        // A API retorna o usuário em userData.data
                        const userInfo = userData.data || userData.user || userData;
                        setUserInfo(userInfo);
                        
                        showMessage('message', 'Login realizado com sucesso!', 'success');
                        setTimeout(() => {
                            window.location.href = 'dashboard.html';
                        }, 1000);
                    } else if (meResponse.status === 401) {
                        // Token inválido ou expirado
                        console.error('Token inválido ou expirado ao buscar /auth/me');
                        clearAuth();
                        showMessage('message', 'Sessão expirada. Tente fazer login novamente.', 'error');
                        setTimeout(() => {
                            window.location.href = 'index.html';
                        }, 2000);
                    } else {
                        const errorData = await meResponse.json().catch(() => ({}));
                        console.error('Erro ao buscar dados do usuário:', meResponse.status, errorData);
                        showMessage('message', errorData.message || 'Erro ao carregar dados do usuário.', 'error');
                    }
                } catch (meError) {
                    console.error('Erro de conexão ao buscar /auth/me:', meError);
                    showMessage('message', 'Erro de conexão ao carregar dados do usuário.', 'error');
                }
            } else {
                showMessage('message', data.message || 'Erro ao fazer login', 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            showMessage('message', 'Erro de conexão. Verifique se a API está rodando.', 'error');
        }
    });
}

// Register Form Handler
const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const full_name = document.getElementById('full_name').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const date_of_birth = document.getElementById('date_of_birth').value;
        const document_number = document.getElementById('document_number').value;
        const country = document.getElementById('country').value;
        const institution = document.getElementById('institution').value;
        const category = document.getElementById('category').value;

        // Limpar mensagens de erro anteriores
        const passwordErrorEl = document.getElementById('passwordError');
        const confirmPasswordErrorEl = document.getElementById('confirmPasswordError');
        if (passwordErrorEl) passwordErrorEl.textContent = '';
        if (confirmPasswordErrorEl) confirmPasswordErrorEl.textContent = '';

        let hasError = false;

        // Validar tamanho da senha (mínimo 8 caracteres)
        if (password.length < 8) {
            if (passwordErrorEl) {
                passwordErrorEl.textContent = 'A senha deve ter pelo menos 8 caracteres.';
            }
            hasError = true;
        }

        // Validar se as senhas coincidem
        if (password !== confirmPassword) {
            if (confirmPasswordErrorEl) {
                confirmPasswordErrorEl.textContent = 'As senhas não coincidem.';
            }
            hasError = true;
        }

        if (hasError) {
            return;
        }

        try {
            const response = await fetch(`${API_BASE_URL}/auth/register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    email, 
                    password, 
                    full_name, 
                    date_of_birth, 
                    document_number, 
                    country, 
                    institution, 
                    category 
                })
            });

            const data = await response.json();

            if (response.ok) {
                showMessage('message', 'Cadastro realizado com sucesso! Redirecionando...', 'success');
                setTimeout(() => {
                    window.location.href = 'index.html';
                }, 2000);
            } else {
                // Exibe mensagem de erro detalhada retornada pela API
                // A API retorna erros no campo 'error' (ex: "E-mail já cadastrado")
                const errorMessage = data.error || 'Erro ao cadastrar. Verifique os dados informados.';
                showMessage('message', errorMessage, 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            showMessage('message', 'Erro de conexão. Verifique se a API está rodando.', 'error');
        }
    });
}

// Logout Handler
const logoutBtn = document.getElementById('logoutBtn');
if (logoutBtn) {
    logoutBtn.addEventListener('click', () => {
        clearAuth();
        window.location.href = 'index.html';
    });
}

// Proteção de páginas autenticadas
function checkAuth() {
    const currentPage = window.location.pathname.split('/').pop();
    const isAuthPage = currentPage === 'index.html' || currentPage === 'register.html' || currentPage === '';
    
    if (!isAuthenticated() && !isAuthPage) {
        redirectToLogin();
        return false;
    }
    
    if (isAuthenticated() && isAuthPage) {
        window.location.href = 'dashboard.html';
        return false;
    }
    
    return true;
}

// Executa verificação de autenticação
checkAuth();
