# Parâmetros da API 
### 1.endpoint - Recebe qual serviço deseja acessar; - Atualmente disponíveis: “salas”, "cadastro", "ativo";
### 2.usuario - Recebe o nome de usuário (Unique ID) do requerente para autenticação; - Aceita somente os registrados no banco 

# Respostas dos endpoints 
### 1. cadastro - Caso o arduino não esteja cadastrado, o cadastra como inativo e retorna sucesso junto do uniqueID fornecido na chamada - Caso o arduino já esteja cadastrado, retorna erro informando que o mesmo já está cadastrado 
### 2. salas - Faz uma chamada para a API de TTS - Retorna a resposta (áudio em  String) para o requerente 
### 3. ativo - Atualiza o status do arduino no banco, confirmando sua atividade - Retorna sucesso e reseta o timer para desativá-lo (a fazer) 

# Site da API para teste
https://apenasumtestezinho.000webhostapp.com/api/api.php?endpoint=ativo&usuario=1234 
https://apenasumtestezinho.000webhostapp.com/api/api.php?endpoint=salas&usuario=1234
