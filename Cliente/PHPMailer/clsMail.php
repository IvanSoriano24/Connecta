<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'ArchivosMailer/Exception.php';
require 'ArchivosMailer/PHPMailer.php';
require 'ArchivosMailer/SMTP.php';

class clsMail {

    private $mail;

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

    public function metEnviar(string $titulo, string $nombre, string $correo, string $asunto, string $bodyHTML) {
        $this->mail->setFrom($this->mail->Username, $titulo); // Remitente
        $this->mail->addAddress($correo, $nombre); // Destinatario
        $this->mail->Subject = $asunto; // Asunto del correo
        $this->mail->Body = $bodyHTML; // Cuerpo del correo
        $this->mail->isHTML(true); // Indicar que el correo tiene contenido HTML

        // Enviar el correo y manejar errores
        if (!$this->mail->send()) {
            return "Error al enviar el correo: " . $this->mail->ErrorInfo;
        }
        return "Correo enviado exitosamente.";
    }
}



$mail = new clsMail();

echo $mail->metEnviar(
    'Mi título',                // Título del remitente
    'Alberto',  // Nombre del destinatario
    'desarrollo02@mdcloud.mx', // Correo del destinatario
    'Asunto del correo',         // Asunto del correo
    '<p>Este es el cuerpo en HTML.</p>' // Cuerpo del correo en HTML
);

?>
