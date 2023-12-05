# Parâmetros da API 
### 1. endpoint 
  - Recebe qual serviço deseja acessar;
  - Atualmente disponíveis: “salas”, "cadastro", "ativo";
### 2. usuario 
  - Recebe o nome de usuário (Unique ID) do requerente para autenticação;
  - Aceita somente os registrados no banco 

# Respostas dos endpoints 
### 1. cadastro 
  - Caso o arduino não esteja cadastrado, o cadastra como inativo
  - Caso o arduino já esteja cadastrado,  não o cadastra
  - Em ambos os casos, retorna um áudio (MP3) informando o ID do requerente
### 2. salas 
  - Faz uma chamada para a API de TTS
  - Retorna a resposta (áudio em  MP3) para o requerente 
### 3. ativo 
  - Confere o status do arduino no banco e atualiza o campo LAST_UPDATE no banco 
  - Retorna o status em string

# Site da API para teste
https://api-arduino.azurewebsites.net/?endpoint=ativo&usuario= <br>
https://api-arduino.azurewebsites.net/?endpoint=salas&usuario=
