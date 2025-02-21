<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'ArchivosMailer/Exception.php';
require 'ArchivosMailer/PHPMailer.php';
require 'ArchivosMailer/SMTP.php';

class clsMail {

    private $mail;

    /*public function __construct(
        $host = 'pop.mdcloud.mx', // Servidor SMTP de tu dominio
        $port = 25, // Puerto SMTP (465 para SSL, 587 para TLS)
        $username = 'desarrollo01@mdcloud.mx', // Correo con dominio propio
        $password = '19058325A!', // Contraseña del correo
        $encryption = PHPMailer::ENCRYPTION_SMTPS // Encriptación (TLS o SSL)
    ) {
        $this->mail = new PHPMailer();
        $this->mail->isSMTP();
        $this->mail->SMTPAuth = true;
        $this->mail->SMTPSecure = $encryption; // Cifrado SSL o TLS
        $this->mail->Host = $host; // Servidor SMTP
        $this->mail->Port = $port; // Puerto SMTP
        $this->mail->Username = $username; // Correo del remitente
        $this->mail->Password = $password; // Contraseña del correo
        $this->mail->CharSet = 'UTF-8'; // Codificación
    }*/
        
    public function __construct() {
        $this->mail = new PHPMailer();
        $this->mail->isSMTP();
        $this->mail->SMTPAuth = true;
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Cifrado TLS
        $this->mail->Host = 'smtp.gmail.com'; // Servidor SMTP de Gmail
        $this->mail->Port = 587; // Puerto para TLS
        $this->mail->Username = 'betovargas584@gmail.com'; // Tu correo de Gmail
        $this->mail->Password = 'tbkn bjyu segx vcgm'; // Tu contraseña o contraseña de aplicación
        $this->mail->CharSet = 'UTF-8'; // Codificación
    }

    /*public function __construct() {
        $this->mail = new PHPMailer();
        $this->mail->isSMTP();
        $this->mail->SMTPAuth = true;
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Cifrado TLS
        $this->mail->Host = 'pop.mdcloud.mx'; // Servidor SMTP de Gmail
        $this->mail->Port = 587; // Puerto para TLS
        $this->mail->Username = 'desarrollo01@mdcloud.mx'; // Tu correo
        $this->mail->Password = '19058325A!'; // Tu contraseña o contraseña de aplicación
        $this->mail->CharSet = 'UTF-8'; // Codificación
        $this->mail->SMTPDebug = 2;
    }*/

    public function metEnviar(string $titulo, string $nombre, string $correo, string $asunto, string $bodyHTML, string $archivoAdjunto = null) {
        try {
            $this->mail->setFrom($this->mail->Username, $titulo); // Remitente
            $this->mail->addAddress($correo, $nombre); // Destinatario
            $this->mail->Subject = $asunto; // Asunto del correo
            $this->mail->Body = $bodyHTML; // Cuerpo del correo
            $this->mail->isHTML(true); // Indicar que el correo tiene contenido HTML

            // **Adjuntar el archivo si existe**
            if (!empty($archivoAdjunto) && file_exists($archivoAdjunto)) {
                $this->mail->addAttachment($archivoAdjunto);
            }

            // Enviar el correo y manejar errores
            if (!$this->mail->send()) {
                return "Error al enviar el correo: " . $this->mail->ErrorInfo;
            }

            // **Eliminar el archivo adjunto después del envío**
            if (!empty($archivoAdjunto) && file_exists($archivoAdjunto)) {
                unlink($archivoAdjunto);
            }

            return "Correo enviado exitosamente.";
        } catch (Exception $e) {
            return "Error al enviar el correo: {$this->mail->ErrorInfo}";
        }
    }
}


/*
$mail = new clsMail();
echo $mail->metEnviar(
    'Mi título',                // Título del remitente
    'Alberto',  // Nombre del destinatario
    'desarrollo02@mdcloud.mx', // Correo del destinatario
    'Asunto del correo',         // Asunto del correo
    '<p>Este es el cuerpo en HTML.</p>' // Cuerpo del correo en HTML
);*/

?>
