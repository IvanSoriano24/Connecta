<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'ArchivosMailer/Exception.php';
require 'ArchivosMailer/PHPMailer.php';
require 'ArchivosMailer/SMTP.php';

class clsMail
{
    private $mail;

    /*private $defaultUser = 'betovargas584@gmail.com'; // Correo por defecto
    private $defaultPass = 'tbkn bjyu segx vcgm'; // Contraseña por defecto*/

    /*private $defaultUser = 'mdc2401042j9@gmail.com'; // Correo por defecto
    private $defaultPass = 'byxj qfob tdvj vbbw'; // Contraseña por defecto*/

    private $defaultUser = 'Servicioalcliente@grupointerzenda.com'; // Correo por defecto
    private $defaultPass = 'aB159263#1#'; // Contraseña por defecto

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
        //$this->mail->Host = 'smtp.gmail.com'; // Servidor SMTP de Gmail
        $this->mail->Host = 'mail.grupointerzenda.com'; 
        //$this->mail->Port = 587; // Puerto para TLS
        $this->mail->Port = 587; // Puerto para TLS
        $this->mail->CharSet = 'UTF-8'; // Codificación
    }

    public function metEnviar(
        string $titulo,
        string $nombre,
        string $correo,
        string $asunto,
        string $bodyHTML,
        string $archivoAdjunto = null,
        string $correoRemitente = null,
        string $passwordRemitente = null,
        string $rutaXml = null,
        string $rutaQr = null,
        string $rutaCfdi = null
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
                if (!empty($rutaCfdi) && file_exists($rutaCfdi)) {
                    $this->mail->addAttachment($archivoAdjunto);
                    $this->mail->addAttachment($rutaCfdi);
                } else {
                    $this->mail->addAttachment($archivoAdjunto);
                }
            }

            // Enviar el correo y manejar errores
            if (!$this->mail->send()) {
                return "Error al enviar el correo: " . $this->mail->ErrorInfo;
            }

            // *Eliminar el archivo adjunto después del envío*
            if (!empty($archivoAdjunto) && file_exists($archivoAdjunto)) {
                if (!empty($rutaCfdi) && file_exists($rutaCfdi)) {
                    unlink($rutaCfdi);
                    unlink($rutaXml);
                    unlink($rutaQr);
                    unlink($archivoAdjunto);
                } else {
                    unlink($archivoAdjunto);
                }
            }
            return "Correo enviado exitosamente.";
        } catch (Exception $e) {
            return "Error al enviar el correo: {$this->mail->ErrorInfo}";
        }
    }

    public function metEnviarError(
        string $titulo,
        string $nombre,
        string $correo,
        string $asunto,
        string $bodyHTML,
        string $correoRemitente = null,
        string $passwordRemitente = null,
        string $rutaXml = null,
        string $rutaError = null
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
            if (!empty($rutaXml) && file_exists($rutaXml)) {
                $this->mail->addAttachment($rutaXml);
            }
            if (!empty($rutaError) && file_exists($rutaError)) {
                $this->mail->addAttachment($rutaError);
            }

            // Enviar el correo y manejar errores
            if (!$this->mail->send()) {
                return "Error al enviar el correo: " . $this->mail->ErrorInfo;
            }

            // *Eliminar el archivo adjunto después del envío*
            if (!empty($rutaXml) && file_exists($rutaXml)) {
                unlink($rutaXml);
            }
            return "Correo enviado exitosamente.";
        } catch (Exception $e) {
            return "Error al enviar el correo: {$this->mail->ErrorInfo}";
        }
    }

    public function metEnviarErrorDatos(
        string $titulo,
        string $nombre,
        string $correo,
        string $asunto,
        string $bodyHTML,
        string $correoRemitente = null,
        string $passwordRemitente = null
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


            // Enviar el correo y manejar errores
            if (!$this->mail->send()) {
                return "Error al enviar el correo: " . $this->mail->ErrorInfo;
            }
            
            return "Correo enviado exitosamente.";
        } catch (Exception $e) {
            return "Error al enviar el correo: {$this->mail->ErrorInfo}";
        }
    }
}
