<?php
    header("Access-Control-Allow-Origin: *");
    include_once("database/conexao.php");

    // verificação de conexão com o banco
    if ($conn->connect_error) {
        $response = array(
            'status' => 'error',
            'message' => 'Erro:' . $conn->connect_error
        );
        echo($response);
        exit;
    }

    
    // se não for para cadastrar o arduino ou registrar atividade
    if ($_GET['endpoint'] != "cadastro" && $_GET['endpoint'] != "ativo") {
        // nomes de usuário permitidos
        $usuarios = 'SELECT * FROM arduino WHERE UNIQUE_ID = "'. $_GET['usuario'] .'";';
        $pegaUsuarios = $conn->query($usuarios);
        $usuarioPermitido = $pegaUsuarios->fetch_assoc();
        // verificar as credencias recebidas
        if ($usuarioPermitido['STATUS_ARDUINO'] != "Ativo" || !isset($usuarioPermitido['STATUS_ARDUINO'])) {
            // se as credenciais estiverem erradas, retorna erro
            header('HTTP/1.0 401 Unauthorized');
            echo ("Usuário não autorizado. \n ID: ". $_GET['usuario']);
            exit;
        } 
    }
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-type');
    
        // verifica o método de requisição
        $method = $_SERVER['REQUEST_METHOD'];
    
        // verifica o endpoint solicitado
        $endpoint = $_GET['endpoint'];
        
        if ($method == 'GET') {
            // endpoint de sala (resposta em áudio)
            if ($endpoint == 'salas') {                
                // consulta ambas as tabelas juntas
                $sql = 'SELECT * FROM sala INNER JOIN arduino ON FK_ARDUINO = ID_ARDUINO WHERE arduino.UNIQUE_ID = "'. $_GET['usuario'] .'";';
                $result = $conn->query($sql);
                $resposta = $result->fetch_assoc();

                // verifica se o query retornou algo
                if (isset($resposta['ID_ARDUINO'])) {
                    $nomeSala = $resposta['NOME_SALA'] ." ". $resposta['NUMERO_SALA'];

                    // pega o áudio com o nome e número da sala
                    $responseSalas = TTS($nomeSala);
                    
                    if (str_contains($responseSalas,'Erro: ')) {
                        // resposta da api com erro e informações, caso ocorra
                        $response = array(
                            'status' => 'Erro Código: '. curl_getinfo($curl, CURLINFO_HTTP_CODE),
                            'erro' => $responseSalas
                        );
                    }
                    else{
                        // devolve a resposta do TTS em String (converter no arduino)
                        $response = $responseSalas;
                    }
                } 
                
                else {
                    // resposta para caso não haja retorno
                    $response = array(
                        'status' => 'error',
                        'message' => 'Erro 404: Sala Não Encontrada.'
                    );
                }

                // fecha conexão com o banco
                $conn->close();
            
            }
            // endpoint cadastro (resposta em áudio)
            elseif ($endpoint == 'cadastro') {
                // checa se já cadastro com aquele unique id
                $sql = 'SELECT * FROM arduino WHERE UNIQUE_ID ="' . $_GET['usuario'] . '"';
                $query = $conn->query($sql);
                $result = $query->fetch_assoc();

                // se houver, retorna que já foi cadastrado
                // em ambos os casos retorna o ID do arduino
                if (!isset($result['UNIQUE_ID']) || $result['UNIQUE_ID'] == '') {
                    $cad = 'INSERT INTO arduino(UNIQUE_ID, STATUS_ARDUINO, LAST_UPDATE) VALUES("'. $_GET['usuario'] .'", "Pendente", NOW());';
                    $resultCad = $conn->query($cad);
                }
                
                $msgCad = "Arduino ID ". $_GET['usuario'];

                // pega o áudio com o ID do arduino
                $responseCad = TTS($msgCad);
                        
                if (str_contains($responseCad,'Erro: ')) {
                    // resposta da api com erro e informações, caso ocorra
                    $response = array(
                        'status' => 'Erro Código: '. curl_getinfo($curl, CURLINFO_HTTP_CODE),
                        'erro' => $responseCad
                    );
                }
                else{
                    // devolve a resposta do TTS em Áudio .mp3
                    $response = $responseCad;                    
                }
            }
            // endpoint de atividade (resposta em string)
            elseif ($endpoint == 'ativo') {
                header('Content-Type: text/html; charset=UTF-8');
                // atualiza o último update do arduino, confirmando sua atividade
                $queryStatus = "SELECT * FROM arduino INNER JOIN sala ON ID_ARDUINO = FK_ARDUINO WHERE UNIQUE_ID = '". $_GET['usuario'] ."';";
                $resultStatus = $conn->query($queryStatus);
                $respStatus = $resultStatus->fetch_assoc();
                if ($respStatus['NOME_SALA'] != "" || $respStatus['NOME_SALA'] != null) {
                    $sql = "UPDATE arduino SET LAST_UPDATE = NOW() WHERE ID_ARDUINO = '". $respStatus['ID_ARDUINO'] ."';";
                    $response = $respStatus['STATUS_ARDUINO'];
                }
                else {
                    $sql = "UPDATE arduino SET LAST_UPDATE = NOW(), STATUS_ARDUINO = 'Ativo' WHERE ID_ARDUINO = '". $respStatus['ID_ARDUINO'] ."';";
                    $response = "Ativo";
                }
                
                $result = $conn->query($sql);                
            }
        }

        if (gettype($response) == "array") {
            // enviar respostas como json
            echo(json_encode($response));
        }
        else {
            echo $response;
        }

        function TTS(string $mensagem) {
            // chave da api de texto para áudio
            $TTSKEY = getenv('APPSETTING_TTS_KEY');

            // headers para o áudio
            header('Content-Description: File Transfer');
            header('Content-Type: audio/mpeg');
            // audio/mpeg
            // audio/x-wav
            header('Content-Transfer-Encoding: binary');

            // curl
            $curl = curl_init();

            // cria o request POST
            curl_setopt_array($curl, [
            CURLOPT_URL => "https://eastus.tts.speech.microsoft.com/cognitiveservices/v1", //url da API
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST", // método de request
            CURLOPT_POSTFIELDS => "<speak version='1.0' xml:lang='pt-BR'><voice xml:lang='pt-BR' xml:gender='Female' name='pt-BR-FranciscaNeural'>\n ". $mensagem ." \n</voice></speak>", // corpo do request
            CURLOPT_HTTPHEADER => [ // headers da chamada 
                "Ocp-Apim-Subscription-Key: ". $TTSKEY, // chave do serviço (menos seguro mas usaria de qualquer jeito pra pegar o token)
                "Content-Type: application/ssml+xml", // tipo do body
                "User-Agent: falamuitoeuespero", // nome do serviço
                "X-Microsoft-OutputFormat: audio-16khz-128kbitrate-mono-mp3" // extensão da resposta (wav)
            ],
            ]);
            // audio-16khz-128kbitrate-mono-mp3
            // riff-24khz-16bit-mono-pcm

            // pega a resposta ou erro da chamada
            $responseTTS = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($responseTTS === "" || !$responseTTS) {
                return("Erro: " . $err);
            }
            else {
                return($responseTTS);
            }
        }

?>
