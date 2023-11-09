<?php
    echo("ola <br>");
    header("Access-Control-Allow-Origin: *");
    $servername = 'doorsense-server.mysql.database.azure.com';
    $username = 'breno';
    $password = 'AcessoTech115';
    $dbname = 'doorsense';

    $conn = new mysqli($servername, $username, $password, $dbname);

    echo("passou dali <br>");
    if (isset($conn)) {
        echo("sem banco");
    }
    else {
        echo("banco");
    }

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
    
    // $senhas = ["senha1", "senha2"]; desnecessário

    // se não for para cadastrar o arduino
    if ($_GET['endpoint'] != "cadastro") {
        // verificar as credencias recebidas
        if (!in_array($_GET['usuario'], $usuariosPermitidos)) {
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
                $statusChg = 'UPDATE arduino SET STATUS_ARDUINO = "Inativo" WHERE UNIQUE_ID = ' . $_GET["usuario"];
                $statusChgd = $conn->query($statusChg);
                
                // verificação de conexão com o banco
                if ($conn->connect_error) {
                    $response = array(
                        'status' => 'error',
                        'message' => 'Erro:' . $conn->connect_error
                    );
                } else {
                    // consulta o banco para pegar o ID do arduino
                    $query = 'SELECT * FROM arduino WHERE UNIQUE_ID = "'. $_GET["usuario"] .'";';
                    $idArduino = $conn->query($query);
                    $pegaId = $idArduino->fetch_assoc();

                    // pega as informações da sala em que o arduino está atribuído
                    $sql = 'SELECT * FROM sala WHERE FK_ARDUINO = '. $pegaId['ID_ARDUINO'];
                    $result = $conn->query($sql);
    
                    // verifica se o query retornou algo
                    if ($result) {
                        // consulta ambas as tabelas juntas
                        $sql = 'SELECT * FROM sala INNER JOIN arduino ON FK_ARDUINO = ID_ARDUINO WHERE arduino.UNIQUE_ID = "'. $_GET['usuario'] .'";';
                        $result = $conn->query($sql);
                        $resposta = $result->fetch_assoc();
                        $nomeSala = $resposta['NOME_SALA'] ." ". $resposta['NUMERO_SALA'];

                        $urlPOST = 'https://eastus.tts.speech.microsoft.com/cognitiveservices/v1'; // link da api de TTS

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
                        CURLOPT_POSTFIELDS => "<speak version='1.0' xml:lang='pt-BR'><voice xml:lang='pt-BR' xml:gender='Female' name='pt-BR-FranciscaNeural'>\n ". $nomeSala ." \n</voice></speak>", // corpo do request
                        CURLOPT_HTTPHEADER => [ // headers da chamada 
                            // Muda depois de um tempo (precisaria de outra chamada de api) // "Authorization: Bearer eyJhbGciOiJFUzI1NiIsImtpZCI6ImtleTEiLCJ0eXAiOiJKV1QifQ.eyJyZWdpb24iOiJlYXN0dXMiLCJzdWJzY3JpcHRpb24taWQiOiI5MTFkOTMyOWY3Mjg0NjVmYjEzMzQwYjU1ZGJlZTZkYSIsInByb2R1Y3QtaWQiOiJTcGVlY2hTZXJ2aWNlcy5GMCIsImNvZ25pdGl2ZS1zZXJ2aWNlcy1lbmRwb2ludCI6Imh0dHBzOi8vYXBpLmNvZ25pdGl2ZS5taWNyb3NvZnQuY29tL2ludGVybmFsL3YxLjAvIiwiYXp1cmUtcmVzb3VyY2UtaWQiOiIvc3Vic2NyaXB0aW9ucy8wNDU0MGNlYy0zMzRhLTQzMjctYWQyOC0zYzNhM2ExOWZlNTcvcmVzb3VyY2VHcm91cHMvZmFsYW50ZXMvcHJvdmlkZXJzL01pY3Jvc29mdC5Db2duaXRpdmVTZXJ2aWNlcy9hY2NvdW50cy9mYWxhbXVpdG9ldWVzcGVybyIsInNjb3BlIjoic3BlZWNoc2VydmljZXMiLCJhdWQiOiJ1cm46bXMuc3BlZWNoc2VydmljZXMuZWFzdHVzIiwiZXhwIjoxNjk1MzE1MjExLCJpc3MiOiJ1cm46bXMuY29nbml0aXZlc2VydmljZXMifQ.3VBKmXGcPrlyjBpk_kYwy5zPSL_vbmPPUuZ1Y9XEhTNTUBLS0olOmvyxPQ49o-T9wzobEcBULxdKe7ae3H_ZnA",
                            "Ocp-Apim-Subscription-Key: d93c29515f6b4fb2a917afa4390d7454", // chave do serviço (menos seguro mas usaria de qualquer jeito pra pegar o token)
                            "Content-Type: application/ssml+xml", // tipo do body
                            "User-Agent: falamuitoeuespero", // nome do serviço
                            "X-Microsoft-OutputFormat: riff-24khz-16bit-mono-pcm" // extensão da resposta (wav)
                        ],
                        ]);

                        // pega a resposta ou erro da chamada
                        $responseTTS = curl_exec($curl);
                        $err = curl_error($curl);
                        curl_close($curl);

                        
                        if ($responseTTS === "" || !$responseTTS) {
                            // resposta da api com erro e informações, caso ocorra
                            $response = array(
                                'status' => 'Erro Código: '. curl_getinfo($curl, CURLINFO_HTTP_CODE),
                                'sala' => $err
                            );
                        }
                        else{
                            // muda o status para ativo já que houve retorno da API
                            $statusChg = 'UPDATE arduino SET STATUS_ARDUINO = "Ativo" WHERE UNIQUE_ID = ' . $_GET["usuario"];
                            $statusChgd = $conn->query($statusChg);

                            // devolve a resposta do TTS em String (converter no arduino)
                            $response = $responseTTS;
                            header('Content-Description: File Transfer');
                            header('Content-Type: audio/x-wav');
                            // header('Content-Disposition: attachment; filename=testfile.wav');
                            header('Content-Transfer-Encoding: binary');
                            // header('transfer-encoding: chunked');
                            // $teste = file_put_contents("audio/".uniqid().".wav", $responseTTS); // salva o arquivo na pasta audio (apenas para teste)
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
                $sql = 'SELECT * FROM arduino WHERE UNIQUE_ID =' . $_GET['usuario'];
                $query = $conn->query($sql);
                $result = $query->fetch_assoc();

                // se houver, retorna erro
                if ($result) {
                    $response = array(
                        'status' => 'error',
                        'message' => 'Erro: Arduino já cadastrado'
                    );
                }
                // caso contrário, cadastra o arduino como inativo, aguardando atribuição de sala
                else {
                    $cad = 'INSERT INTO arduino(UNIQUE_ID, STATUS_ARDUINO, LAST_UPDATE) VALUES("'. $_GET['usuario'] .'", "Inativo", NOW());';
                    $result = $conn->query($cad);

                    $sql = 'SELECT * FROM arduino WHERE UNIQUE_ID =' . $_GET['usuario'];
                    $query = $conn->query($sql);
                    $result = $query->fetch_assoc();
                    $response = array(
                        'status' => 'success',
                        'uniqueid' => $result['UNIQUE_ID']
                    );
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

        if ($endpoint != 'salas') {
            // enviar respostas como json
            echo(json_encode($response));
        }
        else {
            echo $response;
        }

?>
