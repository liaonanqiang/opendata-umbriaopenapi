<?php

namespace Umbria\TelegramBotBundle\UpdateReceiver;

use AnthonyMartin\GeoLocation\GeoLocation;
use JMS\DiExtraBundle\Annotation as DI;
use Shaygan\TelegramBotApiBundle\TelegramBotApi;
use Shaygan\TelegramBotApiBundle\Type\Update;
use Symfony\Component\Config\Definition\Exception\Exception;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use Umbria\OpenApiBundle\Entity\Tourism\GraphsEntities\SportFacility;
use Umbria\OpenApiBundle\Repository\Tourism\GraphsEntities\AttractorRepository;
use Umbria\OpenApiBundle\Repository\Tourism\GraphsEntities\ProposalRepository;

class UpdateReceiver implements UpdateReceiverInterface
{
    private $telegramBotApi;
    private $config;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    public function __construct(TelegramBotApi $telegramBotApi, $config, $em)
    {
        $this->telegramBotApi = $telegramBotApi;
        $this->config = $config;
        $this->em = $em;
    }

    public function handleUpdate(Update $update)
    {
        $arrayOfArraysOfStrings = array(
            array("/about", "/hello","/help","/sport")
        );
        $newKeyboard = new ReplyKeyboardMarkup($arrayOfArraysOfStrings, true, true);
        $message = json_decode(json_encode($update->message), true);

        // LOCATION
        if (isset($message['location'])) {
            $latitude =$message['location']['latitude'];
            $longitude = $message['location']['longitude'];

            // Controllo se all'interno dell'Umbria
            if (($latitude >= 42.36 AND $latitude <= 43.60)
                AND ($longitude >= 11.88 AND $longitude <= 13.25)
            ) {

                $this->telegramBotApi->sendMessage($message['chat']['id'], "Vicino a te puoi trovare questa proposta di visita:");
                $arrayOfMessages = $this->executeProposalQuery($latitude, $longitude, 10);
                $arrayOfMessages1 = $this->executeProposalQuery($latitude, $longitude, 10);

                for ($i = 0; $i < count($arrayOfMessages); $i++) {
                    $this->telegramBotApi->sendMessage($message['chat']['id'], $arrayOfMessages[$i]);
                }
                $this->telegramBotApi->sendMessage($message['chat']['id'], "E i seguenti attrattori:");
                $arrayOfMessages = $this->executeAttractorQuery($latitude, $longitude, 10, false);
                for ($i = 0; $i < count($arrayOfMessages); $i++) {
                    $this->telegramBotApi->sendMessage($message['chat']['id'], $arrayOfMessages[$i]);
                }
                //sportfacility
                for ($i = 0; $i < count($arrayOfMessages1); $i++) {
                    $this->telegramBotApi->sendMessage($message['chat']['id'], $arrayOfMessages1[$i]);
                }
                $this->telegramBotApi->sendMessage($message['chat']['id'], "E i seguenti attrattori:");
                $arrayOfMessages1 = $this->executeSportFacilityQuery($latitude, $longitude, 10, false);
                for ($i = 0; $i < count($arrayOfMessages1); $i++) {
                    $this->telegramBotApi->sendMessage($message['chat']['id'], $arrayOfMessages1[$i]);
                }


            }
            else {
                $arrayOfMessages = $this->executeProposalQuery(43.105275, 12.391995, 100);
                $text = "Ciao " . $message['from']['first_name'] . ". Sei troppo lontano dall'Umbria. Da noi puoi trovare: " . $arrayOfMessages[0];
                $this->telegramBotApi->sendMessage($message['chat']['id'], $text);
                //sportfacility
                $arrayOfMessages1 = $this->executeProposalQuery(43.105275, 12.391995, 100);
                $text1 = "Ciao " . $message['from']['first_name'] . ". Sei troppo lontano dall'Umbria. Da noi puoi trovare: " . $arrayOfMessages1[0];
                $this->telegramBotApi->sendMessage($message['chat']['id'], $text1);
            }

        } else if (isset($message['text'])) {

            switch ($message['text']) {
                case "/about":
                    $text = "UmbriaTourismBot ti permette di ricevere informazioni turistiche. Invia la tua posizione per scoprire tutte le bellezze che la nostra regione ha in serbo per te";
                    break;
                case "/hello":
                    $arrayOfMessages = $this->executeAttractorQuery(43.105275, 12.391995, 100, true);
                    $text = "Ciao " . $message['from']['first_name'] . ". Oggi ti consiglio: " . $arrayOfMessages[0];
                    break;
                case "/help":
                    $arrayOfMessages1 = $this->executeSportFacilityQuery(43.105275, 12.391995, 100, true);
                    $text = "Ciao " . $message['from']['first_name'] . ". Oggi ti consiglio: " . $arrayOfMessages1[0];
                    break;
                case "/start":
                    $text = "UmbriaTourismBot ti permette di ricevere informazioni turistiche. Invia la tua posizione per scoprire tutte le bellezze che la nostra regione ha in serbo per te\n\n";
                default :
                    $text = "Lista comandi:\n";
                    $text .= "/about - Informazioni sul bot\n";
                    $text .= "/hello - Suggerimenti\n";
                    $text .= "/help - Visualizzazione comandi disponibili\n";
                    $text .= "/sprot - Trova impianti sportivi\n";
                    break;
            }

            $newKeyboardCond = $message['text'];
            if (strcmp($newKeyboardCond, "/start") XOR strcmp($newKeyboardCond, "/help")) {
                $this->telegramBotApi->sendMessage($message['chat']['id'], $text, null, false, null, $newKeyboard);
            } else $this->telegramBotApi->sendMessage($message['chat']['id'], $text);
        }

    }

    public function executeAttractorQuery($lat, $lng, $radius, $rand)
    {
        /**@var AttractorRepository $attractorRepo */
        $attractorRepo = $this->em->getRepository('UmbriaOpenApiBundle:Tourism\GraphsEntities\Attractor');

        $location = GeoLocation::fromDegrees($lat, $lng);
        /** @var GeoLocation[] $bounds */
        /** @noinspection PhpInternalEntityUsedInspection */
        $bounds = $location->boundingCoordinates($radius, 'km');

        $pois = $attractorRepo->findByPosition(
            $bounds[1]->getLatitudeInDegrees(),
            $bounds[0]->getLatitudeInDegrees(),
            $bounds[1]->getLongitudeInDegrees(),
            $bounds[0]->getLongitudeInDegrees());

        if (sizeof($pois) > 0) {
            if ($rand) {
                $key = array_rand($pois);

                $poi = $pois[$key];
                $stringResult[0] = $poi->getName() . "\n" . str_replace('&nbsp;', ' ', strip_tags($poi->getShortDescription())) . "\n" . $poi->getResourceOriginUrl();
                return $stringResult;
            } else {
                $i = 0;
                foreach ($pois as $poi) {
                    $stringResult[$i] = $poi->getName() . "\n" . str_replace('&nbsp;', ' ', strip_tags($poi->getShortDescription())) . "\n" . $poi->getResourceOriginUrl();
                    $i++;
                }
                return $stringResult;
            }
        } else {
            throw new Exception();
        }
    }
    public function executeSportFacilityQuery($lat, $lng, $radius, $rand)
    {

    }
    public function executeProposalQuery($lat, $lng, $radius)
    {
        /**@var ProposalRepository $proposalRepo */
        $proposalRepo = $this->em->getRepository('UmbriaOpenApiBundle:Tourism\GraphsEntities\Proposal');

        $location = GeoLocation::fromDegrees($lat, $lng);
        /** @var GeoLocation[] $bounds */
        /** @noinspection PhpInternalEntityUsedInspection */
        $bounds = $location->boundingCoordinates($radius, 'km');

        $pois = $proposalRepo->findByPosition(
            $bounds[1]->getLatitudeInDegrees(),
            $bounds[0]->getLatitudeInDegrees(),
            $bounds[1]->getLongitudeInDegrees(),
            $bounds[0]->getLongitudeInDegrees());

        if (sizeof($pois) > 0) {
            $key = array_rand($pois);
            $poi = $pois[$key];
            $stringResult[0] = $poi->getName() . "\n" . str_replace('&nbsp;', ' ', strip_tags($poi->getShortDescription())) . "\n" . $poi->getResourceOriginUrl();
            return $stringResult;

        } else {
            throw new Exception();
        }
    }

}
