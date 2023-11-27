<?php
    header("Access-Control-Allow-Origin: *");
    include_once("database/conexao.php");


    // nomes de usuário permitidos
    $usuarios = 'SELECT UNIQUE_ID FROM arduino WHERE STATUS_ARDUINO = "Ativo";';
    $pegaUsuarios = $conn->query($usuarios);
    $usuariosPermitidos = array();
    $i = 0;
    while ($row = $pegaUsuarios->fetch_assoc()) {
        $usuariosPermitidos[$i] = $row['UNIQUE_ID'];
        $i++;
    }

    $response = array(
        'status' => 'error',
        'message' => 'Algo deu errado.'
    );

    // se não for para cadastrar o arduino
    if ($_GET['endpoint'] != "cadastro") {
        // verificar as credencias recebidas
        if (!in_array($_GET['usuario'], $usuariosPermitidos, true)) {
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
    
        // verificar os parâmetros de requisição
        $params = $_GET;
    
        // define uma resposta padrão
        $response = array(
            'status' => 'error',
            'message' => 'Resposta Padrão'
        );
        if ($method == 'GET') {
            if ($endpoint == 'salas') {
                // muda o status para inativo para caso haja erro
                $statusChg = 'UPDATE arduino SET STATUS_ARDUINO = "Inativo" WHERE UNIQUE_ID = "' . $_GET["usuario"] . '"';
                $statusChgd = $conn->query($statusChg);
                
                // verificação de conexão com o banco
                if ($conn->connect_error) {
                    $response = array(
                        'status' => 'error',
                        'message' => 'Erro:' . $conn->connect_error
                    );
                } else {
                    // consulta ambas as tabelas juntas
                    $sql = 'SELECT * FROM sala INNER JOIN arduino ON FK_ARDUINO = ID_ARDUINO WHERE arduino.UNIQUE_ID = "'. $_GET['usuario'] .'";';
                    $result = $conn->query($sql);
                    $resposta = $result->fetch_assoc();
    
                    // verifica se o query retornou algo
                    if (isset($resposta['ID_ARDUINO'])) {
                        $nomeSala = "Arduino não está atrelado à uma sala";

                        if (isset($resposta['NOME_SALA']) && isset($resposta['NUMERO_SALA'])) {
                            $nomeSala = $resposta['NOME_SALA'] ." ". $resposta['NUMERO_SALA'];
                        }

                        
                        $responseSalas = TTS($nomeSala);
                        
                        if (str_contains($responseSalas,'Erro: ')) {
                            // resposta da api com erro e informações, caso ocorra
                            $response = array(
                                'status' => 'Erro Código: '. curl_getinfo($curl, CURLINFO_HTTP_CODE),
                                'sala' => $responseSalas
                            );
                        }
                        else{
                            // muda o status para ativo já que houve retorno da API
                            $statusChg = 'UPDATE arduino SET STATUS_ARDUINO = "Ativo" WHERE UNIQUE_ID = "' . $_GET["usuario"] . '"';
                            $statusChgd = $conn->query($statusChg);

                            // devolve a resposta do TTS em String (converter no arduino)
                            $response = $responseSalas;
                            header('Content-Description: File Transfer');
                            header('Content-Type: audio/mpeg');
                            header('Content-Transfer-Encoding: binary');
                            
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
            }
            // endpoint cadastro
            elseif ($endpoint == 'cadastro') {
                // checa se já cadastro com aquele unique id
                $sql = 'SELECT * FROM arduino WHERE UNIQUE_ID ="' . $_GET['usuario'] . '"';
                $query = $conn->query($sql);
                $result = $query->fetch_assoc();

                // se houver, retorna erro
                if (!isset($result['UNIQUE_ID'])) {
                    $cad = 'INSERT INTO arduino(UNIQUE_ID, STATUS_ARDUINO, LAST_UPDATE) VALUES("'. $_GET['usuario'] .'", "Inativo", NOW());';
                    $result = $conn->query($cad);

                    $sql = 'SELECT * FROM arduino WHERE UNIQUE_ID =' . $_GET['usuario'];
                    $query = $conn->query($sql);
                    $result = $query->fetch_assoc();

                    $msgCad = "Arduino ID ". $result['UNIQUE_ID'];
                }
                else {
                    $msgCad = "Arduino já cadastrado";
                }

                $responseCad = TTS($msgCad);
                        
                if (str_contains($responseCad,'Erro: ')) {
                    // resposta da api com erro e informações, caso ocorra
                    $response = array(
                        'status' => 'Erro Código: '. curl_getinfo($curl, CURLINFO_HTTP_CODE),
                        'sala' => $responseCad
                    );
                }
                else{
                    // devolve a resposta do TTS em Áudio .mp3
                    $response = $responseCad;
                    header('Content-Description: File Transfer');
                    header('Content-Type: audio/mpeg');
                    header('Content-Transfer-Encoding: binary');
                    
                }
            }
            // endpoint de atividade
            elseif ($endpoint == 'ativo') {
                // atualiza o estado do arduino para ativo, confirmando sua atividade
                $sql = "UPDATE arduino SET STATUS_ARDUINO = 'Ativo', LAST_UPDATE = NOW() WHERE UNIQUE_ID = '". $_GET['usuario'] ."';";
                $result = $conn->query($sql);
                $response = array(
                    'status' => 'success',
                    'status_arduino' => 'Ativo'
                );
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
                "Ocp-Apim-Subscription-Key: d93c29515f6b4fb2a917afa4390d7454", // chave do serviço (menos seguro mas usaria de qualquer jeito pra pegar o token)
                "Content-Type: application/ssml+xml", // tipo do body
                "User-Agent: falamuitoeuespero", // nome do serviço
                "X-Microsoft-OutputFormat: audio-16khz-128kbitrate-mono-mp3" // extensão da resposta (wav)
            ],
            ]);

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
