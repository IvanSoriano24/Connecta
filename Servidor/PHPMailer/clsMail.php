<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'ArchivosMailer/Exception.php';
require 'ArchivosMailer/PHPMailer.php';
require 'ArchivosMailer/SMTP.php';

class clsMail
{
    private $mail;
    private $defaultUser = 'betovargas584@gmail.com'; // Correo por defecto
    private $defaultPass = 'tbkn bjyu segx vcgm'; // Contraseña por defecto

    /*private $defaultUser = 'sonicjos.ys@gmail.com'; // Correo por defecto
    private $defaultPass = 'dnfb fyvb qpuk xqml'; // Contraseña por defecto*/

    //private $defaultUser = 'josemanuelnavarroreval@gmail.com'; // Correo por defecto
    //private $defaultPass = 'ntdc qhcf ymxm guks'; // Contraseña por defecto

    public function __construct()
    {
        $this->mail = new PHPMailer();
        $this->mail->isSMTP();
        $this->mail->SMTPAuth = true;
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Cifrado TLS
        $this->mail->Host = 'smtp.gmail.com';
        //$this->mail->Host = 'mail.grupointerzenda'; // Servidor SMTP de Gmail
        $this->mail->Port = 587; // Puerto para TLS
        //$this->mail->Port = 995; // Puerto para TLS
        $this->mail->CharSet = 'UTF-8'; // Codificación
    }

    public function metEnviar(
        string $titulo,
        string $nombre,
        string $correo,
        string $asunto,
        string $bodyHTML,
        string $archivoAdjunto = null,
        string $rutaCfdi = null,
        string $correoRemitente = null,
        string $passwordRemitente = null,
        string $rutaXml = null,
        string $rutaQr = null
    ) {
        try {
            if ($correoRemitente === "" || $passwordRemitente === "") {
                $remitente = $this->defaultUser;
                $password = $this->defaultPass;
            } else {
                // Usar remitente y contraseña por defecto si no se proporciona
                $remitente = $correoRemitente;
                $password = $passwordRemitente;
            }
            
            $this->mail->Username = $remitente;
            $this->mail->Password = $password;
            $this->mail->setFrom($remitente, $titulo); // Remitente dinámico o por defecto
            $this->mail->addAddress($correo, $nombre); // Destinatario
            $this->mail->Subject = $asunto; // Asunto del correo
            $this->mail->Body = $bodyHTML; // Cuerpo del correo
            $this->mail->isHTML(true); // Indicar que el correo tiene contenido HTML

            // *Adjuntar el archivo si existe*
            if (!empty($archivoAdjunto) && file_exists($archivoAdjunto)) {
                $this->mail->addAttachment($archivoAdjunto);
                $this->mail->addAttachment($rutaCfdi);
            }

            // Enviar el correo y manejar errores
            if (!$this->mail->send()) {
                return "Error al enviar el correo: " . $this->mail->ErrorInfo;
            }

            // *Eliminar el archivo adjunto después del envío*
            /*if (!empty($archivoAdjunto) && file_exists($archivoAdjunto)) {
                if(!empty($rutaCfdi) && file_exists($rutaCfdi)){
                    unlink($rutaCfdi);
                    unlink($rutaXml);
                    unlink($rutaQr);
                    unlink($archivoAdjunto);
                }else{
                    unlink($archivoAdjunto);
                }
            }*/
            return "Correo enviado exitosamente.";
        } catch (Exception $e) {
            return "Error al enviar el correo: {$this->mail->ErrorInfo}";
        }
    }
}

// Ejemplo de uso:
// $mail = new clsMail();
// echo $mail->metEnviar(
//     'Mi título',                      // Título del remitente
//     'Alberto',                         // Nombre del destinatario
//     'desarrollo02@mdcloud.mx',         // Correo del destinatario
//     'Asunto del correo',               // Asunto del correo
//     '<p>Este es el cuerpo en HTML.</p>', // Cuerpo del correo en HTML
//     null,                              // No se adjunta archivo
//     'otroremitente@gmail.com',         // Remitente dinámico
//     'su-contraseña-aqui'               // Contraseña del remitente
// );
