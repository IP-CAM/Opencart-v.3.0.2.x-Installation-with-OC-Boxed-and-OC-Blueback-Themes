<?php
// Heading
$_['heading_title']                = 'FAN Courier - Romania';

$_['text_shipping']    = 'Shipping';
$_['text_success']     = 'Success: You have modified FAN Courier shipping!<br>Va multumim pentru ca folositi serviciile FAN Courier. Pentru intrebari sau nelamuriri nu ezitati sa ne contactati la email: <a href="mailto:selfawb@fancourier.ro">selfawb@fancourier.ro</a>';
$_['text_extension']   = 'Extensions';
// Text
$_['entry_status']                 = 'A a se utiliza modulul FAN Courier';
$_['entry_clientid']               = 'Client ID';
$_['help_entry_clientid']		   = 'Client ID oferit de FAN Courier.';
$_['entry_username']               = 'Cont utilizator';
$_['help_entry_username']          = 'Cont utilizator selfAWB.';
$_['entry_password']               = 'Parola';
$_['help_entry_password']          = 'Parola cont selfAWB.';
$_['entry_onlyadm']                = 'Confirmare AWB de catre Admin';
$_['entry_parcel']                 = 'Expediere colete';
$_['text_labels']                  = 'Numar pachete / AWB';
$_['help_text_labels']             = 'Introduceti un numar intreg. Exemplu: 1 - daca expediati 1 pachet / AWB.';
$_['entry_paymentdest']            = 'Plata AWB la destinatie';
$_['entry_fara_tva']               = 'Afisare pret fara TVA';

//afisare pret km suplimentari
$_['entry_doar_km']				   = 'Afisare pret doar km suplimentari';
$_['help_entry_doar_km']		   = 'In cadrul acestei optiuni este necesar sa se seteze <b>Plata AWB la destinatie - Nu</b> si <b>Adaugare taxa transp. la ramburs - Da</b>';
//sfarsit afisare pret km suplimentari

$_['entry_payment0']               = 'Ascundere taxa de transport';
$_['text_min_gratuit']             = 'Suma minima transport gratuit';
//alex g valoare fixa
$_['text_valoare_fixa']			   = 'Valoare fixa pentru transport in tara';
//sfarsit valoare fixa

//alex g valoare fixa bucuresti
$_['text_valoare_fixa_bucuresti']  = 'Valoare fixa pentru transport in Bucuresti';
//sfarsit valoare fixa bucuresti

$_['entry_ramburs']                = 'Solicitare ramburs valoare marfa';
$_['entry_totalrb']                = 'Adaugare taxa transp. la ramburs';
$_['entry_contcolector']           = 'Solicitare ramburs in cont bancar';
$_['entry_paymentrbdest']          = 'Plata ramburs la destinatie';
$_['help_entry_paymentrbdest']     = 'Nu se aplica pentru serviciile de tip Cont Colector.';
$_['entry_asigurare']              = 'Solicitare asigurare de transport';
$_['entry_content']                = 'Include cod produse la continut';
$_['entry_comment']                = 'Observatii (imprimare pe AWB)';
$_['entry_pers_cont_exp']          = 'Persoana de contact';
$_['entry_redcode']                = 'Afisare optiune RedCode';
$_['entry_express']                = 'Afisare optiune ExpressLoco';
$_['entry_ridicare_keba']		   = 'Afisare optiune livrare in punct fix (eBOX)';
$_['entry_ridicare_paypoint']	   = 'Afisare optiune livrare in punct fix (PayPoint)';
$_['entry_deschidere_livrare']	   = 'Deschidere la livrare';
$_['entry_epod']	               = 'Utilizare optiune ePOD';
$_['help_entry_deschidere_livrare']= 'In cadrul acestei optiuni este necesar sa se seteze <b>Plata AWB la destinatie - Nu</b>';
$_['help_entry_epod']              = 'Generare AWB cu optiunea ePOD';
$_['error_clientid']               = 'Campul "ClientID" este necesar.';
$_['error_username']               = 'Campul "Utilizator" este necesar.';
$_['error_password']               = 'Campul "Parola" este necesar.';
$_['error_labels']                 = 'Campul "Numar pachete / AWB" este necesar. Completati o valoare numerica pozitiva si diferita de 0.';
$_['error_min_gratuit']            = 'Completati o valoare numerica diferita de 0 pentru ascunderea taxei de transport la comenzi ce depasesc aceasta valoare. Exemplu: 100.';
//alex g valoare fixa
$_['error_valoare_fixa']           = 'Completati o valoare numerica diferita de 0 pentru setarea unei taxe de transport fixa in tara. Exemplu: 10.';
//sfarsit valoare fixa
//alex g valoare fixa bucuresti
$_['error_valoare_fixa_bucuresti'] = 'Completati o valoare numerica diferita de 0 pentru setarea unei taxe de transport fixa in Bucuresti. Exemplu: 10.';
//sfarsit valoare fixa bucuresti

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify FAN Courier shipping!';
?>