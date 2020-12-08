<?php
class ModelExtensionShippingFANCourier extends Model {
	public function getQuote($address) {
		$this->load->language('extension/shipping/fancourier');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country where country_id = '" . (int)$address['country_id'] . "' and name='Romania'");
	
		if ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		if ($status) {
                        $username = $this->config->get('shipping_fancourier_username');
                        $parola = $this->config->get('shipping_fancourier_password');
                        $clientid = $this->config->get('shipping_fancourier_clientid');
                        $parcel = $this->config->get('shipping_fancourier_parcel');
                        $labels = $this->config->get('shipping_fancourier_labels');
                        $ramburs = $this->config->get('shipping_fancourier_ramburs');
                        $content = $this->config->get('shipping_fancourier_content');
                        $contcolector = $this->config->get('shipping_fancourier_contcolector');
                        $redcode = $this->config->get('shipping_fancourier_redcode');
                        $express = $this->config->get('shipping_fancourier_express');
						$paypoint = $this->config->get('shipping_fancourier_ridicare_paypoint');
                        $keba = $this->config->get('shipping_fancourier_ridicare_keba');
					
					    $paymentdest = $this->config->get('shipping_fancourier_paymentdest');
                        $paymentrbdest = $this->config->get('shipping_fancourier_paymentrbdest');
                        $payment0 = $this->config->get('shipping_fancourier_payment0');
                        $min_gratuit = $this->config->get('shipping_fancourier_min_gratuit');
                        //alex g valoare fixa
                        $valoare_fixa = $this->config->get('shipping_fancourier_valoare_fixa');
                        //sfarsit valoare fixa 
                        //alex g valoare fixa bucuresti
                        $valoare_fixa_bucuresti = $this->config->get('shipping_fancourier_valoare_fixa_bucuresti');
                        //sfarsit valoare fixa bucuresti                      
                        $observatii = $this->config->get('fancourier_comment');	
			$pers_contact_expeditor = $this->config->get('fancourier_pers_contact_expeditor');
			$deschidere_la_livrare = $this->config->get('fancourier_deschidere_livrare');
                        $epod = $this->config->get('fancourier_epod');
						
			$asigurare = $this->config->get('shipping_fancourier_asigurare');
                        $totalrb = $this->config->get('shipping_fancourier_totalrb');
                        $onlyadm = $this->config->get('shipping_fancourier_onlyadm');
                        $fara_tva = $this->config->get('shipping_fancourier_fara_tva');						
			//valoare km suplimentari
			$doar_km = $this->config->get('fancourier_doar_km');
			//sfarsit valoare km suplimentari
						
						
                        //$msg = "Comanda nu a fost procesata de catre FAN Courier.<br>Va rugam sa corectati datele de livrare conform mesajului de mai jos: <br><br>";
			$msg = '';
			            $optiuni = '';

                        if ($deschidere_la_livrare == 'A') $optiuni .= 'A';
                        if ($epod == 'X') $optiuni .= 'X';
			
                        $method_data = array();
                        $error = '';

                        if (is_numeric($min_gratuit)) $min_gratuit = $min_gratuit + 0; else $min_gratuit = 0 + 0;
                        
						//alex g valoare fixa
						if (is_numeric($valoare_fixa)) $valoare_fixa = $valoare_fixa + 0; else $valoare_fixa = 0 + 0;
                        //sfarsit valoare fixa
                        
						//alex g valoare fixa
						if (is_numeric($valoare_fixa_bucuresti)) $valoare_fixa_bucuresti = $valoare_fixa_bucuresti + 0; else $valoare_fixa_bucuresti = 0 + 0;
                        //sfarsit valoare fixa
											
						$link_standard="";
											
                        if ($parcel){
                            $plic="0";
                            if (is_numeric($labels)){
                                $colet=$labels;
                            } else {
                                $colet=1;
                            }
                        } else {
                            $colet="0";
                            if (is_numeric($labels)){
                                $plic=$labels;
                            } else {
                                $plic=1;
                            }
                        }

                        if ($totalrb){
                            $totalrb = "1";
                        } else {
                            $totalrb = "0";
                        }

                        if ($this->cart->hasProducts()){

                                    if ($asigurare){
                                            $valoaredeclarata = number_format(round((float)$this->cart->getTotal(),2), 2, '.', '');
                                    } else {
                                            $valoaredeclarata = 0;
                                    }

                                    $greutate = number_format(round((float)$this->cart->getWeight(),0), 0, '.', '');
									//
									if ($greutate>1)
									{
										$plic=0;
										if (is_numeric($labels))
										{
											$colet=$labels;
										} 
										else 
										{
											$colet=1;
										}
									}
									
									
									//
                                    if (round((float)$this->cart->getWeight(),0)>5) $redcode = false;

                                    $lungime=0;
                                    $latime=0;
                                    $inaltime=0;

                                    if ($paymentdest){
                                        $plata_expeditiei="destinatar";
                                    }else{
                                        $plata_expeditiei="expeditor";
                                    }

                                    $rambursare = '';
                                    $rambursare_number = 0 + 0;

                                    $plata_expeditiei_ramburs = "";
                                    
                                    //alex g comutare intre valoarea fixa bucuresti si valoarea fixa pe tara
                                    if ((strtolower($address['city'])=="bucuresti") and is_numeric($valoare_fixa_bucuresti))
                                    {
                                    	$valoare_fixa=$valoare_fixa_bucuresti;
                                    }
                                    //alex g sfarsit comutare
                                    
                                    
                                    if ($ramburs)
									{
                                        if ($contcolector)
										{
                                            $rambursare = number_format(round((float)$this->cart->getTotal(),2), 2, '.', '');
                                            $rambursare_number = round((float)$this->cart->getTotal(),2)+0;                                          
                                            
                                            if ($min_gratuit<$rambursare_number and $min_gratuit!=0)
                                            {							
                                            	$totalrb="0";
                                            }
                                                                                       
                                            if ($paymentrbdest)
											{
                                                $plata_expeditiei_ramburs="destinatar";
                                            }
											else
											{
                                                $plata_expeditiei_ramburs="expeditor";
                                            }
                                        } 
										else 
										{                                       	
                                            $rambursare = (string)number_format(round((float)$this->cart->getTotal(),2), 2, '.', '')." LEI";
                                            $rambursare_number = round((float)$this->cart->getTotal(),2)+0;
                                                                                        											
											if ($min_gratuit<$rambursare_number and $min_gratuit!=0)
                                            {
                                            	$totalrb="0";
                                            }
                                            
                                            if ($paymentrbdest)
											{
                                                $plata_expeditiei_ramburs="destinatar";
                                            }
											else
											{
                                                $plata_expeditiei_ramburs="expeditor";
                                            }
                                        }
                                    } 
									else 
									{
                                        $rambursare_number = round((float)$this->cart->getTotal(),2)+0;
                                    }
									
									//cand min gratuit  mai mic ca ramburs trec automat plata la expeditor
                                        if ($min_gratuit<$rambursare_number and $min_gratuit!=0)
										{
											$plata_expeditiei="expeditor";
										}
									//sfarsit

                                    $localitate_dest = $address['city'];

                                    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone where zone_id = " . (int)$address['zone_id'] . " limit 0, 1");
                                    $judet_dest = '';
                                    if ($query->num_rows) {
                                        $judet_dest = $query->row['name'];
                                    }
                                    
                                    if ($judet_dest=="Satu-Mare")
                                    {
                                        $judet_dest="Satu Mare";
                                    }

                                    if ($judet_dest=="Dimbovita")
                                    {
                                        $judet_dest="Dambovita";
                                    }                                   
										
										
									
                                    $continut='';
                                    
									 
									$inaltime_ebox = array();
									$lungime_ebox = array();
									$latime_ebox = array();
									$k=0;
									
                                        foreach ($this->cart->getProducts() as $product) 
										{
                                            //if ($product['shipping']) 
											//{												
                                            //    $continut = $continut.$product["quantity"]." X ".$product["name"].", ";												
                                            //}
											
											
                                            $inaltime_ebox[$k] = number_format($product["height"],2);
                                            $lungime_ebox[$k] = number_format($product["length"],2);
                                            $latime_ebox[$k] = number_format($product["width"],2);

                                            $produs[$k] = array( $inaltime_ebox[$k],  $latime_ebox[$k], $lungime_ebox[$k] );
                                            
                                            $k++;
											
                                        }	
											
										$l=0;
                                        foreach($produs as $prod)
                                        {
                                            asort($prod);
                                            $latime_sum[$l] = 20;
                                            $inaltime_sum[$l] = 20;
                                            $l++;
                                        }
										
										   
                                    //setez lungime -> ce mai mare dintre dimensiuni
									$lungime = max(array_merge($inaltime_ebox, $lungime_ebox, $latime_ebox));
									$latime = array_sum($latime_sum);
									$inaltime = array_sum($inaltime_sum);
                                    //}
                                    //if ($continut!=""){$continut=substr($continut, 2);}						
									//$continut=$this->request->post['telephone'];
									
									
                                    if(trim($address["company"])!='')
									{
                                        $nume_destinatar =$address["company"];
                                        $persoana_contact =  $address["firstname"]." ".$address["lastname"];
                                        if (trim($persoana_contact) == '')$this->customer->getFirstName()." ".$this->customer->getLastName();
                                    } 
									else 
									{
										$nume_destinatar = $address["firstname"]." ".$address["lastname"];
										if ($nume_destinatar == ' ')$this->customer->getFirstName()." ".$this->customer->getLastName();
										$persoana_contact = $this->customer->getFirstName()." ".$this->customer->getLastName();
										if (trim($persoana_contact) == trim($persoana_contact)) $persoana_contact = '';
                                    }
																
							
                                    $telefon = $this->customer->getTelephone();
                                    //if (!is_null($this->customer->getFax()) and $this->customer->getFax()!='') $telefon=$telefon." / ".$this->customer->getFax();

                                    if (isset($this->session->data['guest']['telephone'])) 
									{
                                        $telefon = $this->session->data['guest']['telephone'] ;
                                    }
	
													if (isset($address['custom_field']['11']) && !empty($address['custom_field']['11']))
													{
														$telefon = $address['custom_field']['11'];
													}
																	
                                    
                                    //alex g 12.02.2014 transmitere adresa de mail catre selfawb
									$email = $this->customer->getEmail();
                        			if (isset($this->session->data['guest']['email'])) {
                                        $email = $this->session->data['guest']['email'] ;
                                    }									
                                    //sfarsit alex g


                                    $strada = $address['address_1'];
									$strada = rawurlencode($strada);
                                    if ($address['address_1']!=''){
										$temp_adress = isset($address['address_2']) ? $address['address_2'] : '';
										$temp_adress = rawurlencode($temp_adress);
                                        $strada = $strada.", ".$temp_adress;
                                    }
                                    
                                    $postalcode = str_pad($address['postcode'], 6 ,"0");

                                    
									
									$url = 'https://www.selfawb.ro/order.php';
                                    $c = curl_init ($url);
                                    curl_setopt ($c, CURLOPT_POST, true);
                                    curl_setopt ($c, CURLOPT_POSTFIELDS, "username=$username&user_pass=$parola&client_id=$clientid&return=services&keba=$keba&ridicare=$paypoint&greutate=$greutate&ramburs=$rambursare&telefon=$telefon&plata=$plata_expeditiei");
                                    curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
                                    $page = curl_exec ($c);
                                    curl_close ($c);

                                    //$servicii_data = str_getcsv($page,"\n"); // COMPATIBIL PENTRU VERSIUNE PHP > 5.3.X
                                    $servicii_data = explode("\n",ltrim(rtrim($page))); // COMPATIBIL PENTRU VERSIUNE PHP < 5.2.X
                                     //print_r($servicii_data);
                                    foreach($servicii_data as $tip_serviciu_info){
                                        
										$tip_serviciu_info = str_replace('"','',$tip_serviciu_info);
										
                                        if ((!$contcolector or round($ramburs, 0)==0)){
                                            $tip_serviciu = explode(",",$tip_serviciu_info);
											
											
											//daca e Standard -> doar aici pt Keba
                                            if ($tip_serviciu[1]==0 and (($tip_serviciu[2]==0 and $tip_serviciu[3]==0) or ($tip_serviciu[2]==1 and $redcode) or ($tip_serviciu[3]==1 and $express))){
												
												
												
												//setare optiuni in functie de serviciu START
                                                if(strpos($tip_serviciu[0], 'Keba') !== false )
                                                {
                                                    $optiuni = 'H';
                                                }else{
                                                    if(strpos($tip_serviciu[0], 'Collect') !== false ){
                                                        $optiuni = 'F';
                                                    }
                                                }
											
                                                //setare optiuni in functie de serviciu START
												//print_r($tip_serviciu[0]);
												
                                                    $url = 'https://www.selfawb.ro/order.php';
                                                    $c = curl_init ($url);
                                                    curl_setopt ($c, CURLOPT_POST, true);
                                                    curl_setopt ($c, CURLOPT_POSTFIELDS, "username=$username&user_pass=$parola&client_id=$clientid&plata_expeditiei=$plata_expeditiei&tip_serviciu=$tip_serviciu[0]&localitate_dest=$localitate_dest&judet_dest=$judet_dest&plic=$plic&colet=$colet&greutate=$greutate&lungime=$lungime&latime=$latime&inaltime=$inaltime&valoare_declarata=$valoaredeclarata&plata_ramburs=$plata_expeditiei_ramburs&ramburs=$rambursare&pers_contact_expeditor=$pers_contact_expeditor&observatii=$observatii&continut=$continut&nume_destinatar=$nume_destinatar&persoana_contact=$persoana_contact&telefon=$telefon&email=$email&strada=$strada&postalcode=$postalcode&totalrb=$totalrb&admin=$onlyadm&fara_tva=$fara_tva&suma_fixa=$valoare_fixa&doar_km=$doar_km&optiuni=$optiuni&keba=$keba&ridicare=$paypoint");
                                                    curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
                                                    $page = curl_exec ($c);
                                                    curl_close ($c);
                                                    $price = explode("|||",$page);
													
                                                    if (isset($price[1])) {
                                                        
                                                    	if (!($payment0) and ($min_gratuit>$rambursare_number or $min_gratuit==0)) 
                                                    	{
                                                        $price_standard = $price[0]; 														
                                                    	}
                                                        else 
                                                        $price_standard = 0;
                                                        
                                                        $link_standard = $price[1];
                                                       
                                                    } else {
                                                        $price_standard = "";
                                                    }
													
													// verificare serviciu Collect Point START
													 if(strpos($tip_serviciu[0], 'Keba') !== false )
                                                {
                                                    $link_generare = "<a id=\"ebox\" href=\"https://www.selfawb.ro/order.php?order_id=$link_standard&type=ebox\" target=\"_blank\">Ridicare din punct fix (eBOX)</a>";
                                                }
                                                else
                                                {
                                                    if(strpos($tip_serviciu[0], 'Collect') !== false )
                                                    {
                                                        $link_generare = "<a id=\"collect_point\" href=\"https://www.selfawb.ro/order.php?order_id=$link_standard&type=collect\" target=\"_blank\">Ridicare din punct fix (PayPoint)</a>";
                                                    }
                                                    else
                                                    {
                                                        $link_generare = "<a href=\"https://www.selfawb.ro/order.php?order_id=$link_standard\" target=\"_blank\">$tip_serviciu[0]</a>";
                                                    }
                                                }
													
													// verificare serviciu Collect Point STOP

                                                    if (is_numeric($price_standard) and $link_standard!=""){
                                                            $currency = 'RON';
                                                            $quote_data[str_replace(" ","_",$tip_serviciu[0])] = array(
                                                                'code'         => 'fancourier.'.str_replace(" ","_",$tip_serviciu[0]),
                                                                'title'        => $link_generare,
                                                                'cost'         => $this->currency->convert($price_standard, $currency, $this->config->get('config_currency')),
                                                                'tax_class_id' => 'fancourier.tax',
                                                                'text'         => $this->currency->format($this->tax->calculate($this->currency->convert($price_standard, $currency, $this->config->get('config_currency') ), $this->config->get('fan_class_id'), $this->config->get('config_tax')), $this->config->get('config_currency') , 1.0000000)
                                                            );
															//print_r($quote_data);echo 1;exit;
                                                    }else{
                                                            if ($tip_serviciu[2]==0 and $tip_serviciu[3]==0){
                                                                $quote_data[str_replace(" ","_",$tip_serviciu[0])] = array(
                                                                    'code'         => 'fancourier.'.str_replace(" ","_",$tip_serviciu[0]),
                                                                    'title'        => "<a href=\"https://www.selfawb.ro/order.php?order_id=$link_standard\" target=\"_blank\">$tip_serviciu[0]</a>",
                                                                    'cost'         => 0,
                                                                    'tax_class_id' => 'fancourier.tax.'.str_replace(" ","_",$tip_serviciu[0]),
                                                                    'text'         => $msg.$page
                                                                );
																//print_r($quote_data);echo 2;exit;
                                                                $error = true;
                                                            }
                                                    }
                                            }

                                            unset($tip_serviciu);
                                        } else {
                                            $tip_serviciu = explode(",",$tip_serviciu_info);
                                            if ($tip_serviciu[1]==1 and (($tip_serviciu[2]==0 and $tip_serviciu[3]==0) or ($tip_serviciu[2]==1 and $redcode) or ($tip_serviciu[3]==1 and $express))){
												
												 
                                                    if(strpos($tip_serviciu[0], 'Collect') !== false ){
                                                        $optiuni = 'F';
                                                    } 
                                              
												
                                                    $url = 'https://www.selfawb.ro/order.php';
                                                    $c = curl_init ($url);
                                                    curl_setopt ($c, CURLOPT_POST, true);
                                                    curl_setopt ($c, CURLOPT_POSTFIELDS, "username=$username&user_pass=$parola&client_id=$clientid&plata_expeditiei=$plata_expeditiei&tip_serviciu=$tip_serviciu[0]&localitate_dest=$localitate_dest&judet_dest=$judet_dest&plic=$plic&colet=$colet&greutate=$greutate&lungime=$lungime&latime=$latime&inaltime=$inaltime&valoare_declarata=$valoaredeclarata&plata_ramburs=$plata_expeditiei_ramburs&ramburs=$rambursare&pers_contact_expeditor=$pers_contact_expeditor&observatii=$observatii&continut=$continut&nume_destinatar=$nume_destinatar&persoana_contact=$persoana_contact&telefon=$telefon&email=$email&strada=$strada&postalcode=$postalcode&totalrb=$totalrb&admin=$onlyadm&fara_tva=$fara_tva&suma_fixa=$valoare_fixa&doar_km=$doar_km&optiuni=$optiuni&ridicare=$paypoint");
                                                    curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
                                                    $page = curl_exec ($c);
                                                    curl_close ($c);
                                                    $price = explode("|||",$page);
													
                                                    if (!($payment0) and ($min_gratuit>$rambursare_number or $min_gratuit==0)) $price_standard = $price[0]; else $price_standard = 0;
                                                    if (isset($price[1])) {
                                                        if (!($payment0) and ($min_gratuit>$rambursare_number or $min_gratuit==0)) 
                                                        {
                                                        $price_standard = $price[0]; 														
                                                        }
                                                        else 
                                                        $price_standard = 0;
                                                        
                                                        $link_standard = $price[1];
                                                        
                                                        
                                                    } else {
                                                        $price_standard = "";
                                                    }
													
												// verificare serviciu Collect Point START
												if(strpos($tip_serviciu[0], 'Collect') !== false ){
													
                                                    $link_generare = "<a id=\"collect_point\" href=\"https://www.selfawb.ro/order.php?order_id=$link_standard&type=collect\" target=\"_blank\">Ridicare din punct fix (PayPoint)</a>";
                                                    //print_r($link_generare);exit;
												}else{
                                                    $link_generare = "<a href=\"https://www.selfawb.ro/order.php?order_id=$link_standard\" target=\"_blank\">$tip_serviciu[0]</a>";
                                                }
												// verificare serviciu Collect Point STOP

                                                    if (is_numeric($price_standard) and $link_standard!=""){
														
                                                            $currency = 'RON';
                                                            $quote_data[str_replace(" ","_",$tip_serviciu[0])] = array(
                                                                'code'         => 'fancourier.'.str_replace(" ","_",$tip_serviciu[0]),
                                                                'title'        => $link_generare,
                                                                'cost'         => $this->currency->convert($price_standard, $currency, $this->config->get('config_currency')),
                                                                'tax_class_id' => 'fancourier.tax',
                                                                'text'         => $this->currency->format($this->tax->calculate($this->currency->convert($price_standard, $currency, $this->config->get('config_currency') ), $this->config->get('fan_class_id'), $this->config->get('config_tax')), $this->config->get('config_currency') , 1.0000000)
                                                            );
															//print_r($quote_data);echo 3;exit;
															
                                                    }else{
														
                                                            if ($tip_serviciu[2]==0 and $tip_serviciu[3]==0){
                                                                $quote_data[str_replace(" ","_",$tip_serviciu[0])] = array(
                                                                    'code'         => 'fancourier.'.str_replace(" ","_",$tip_serviciu[0]),
                                                                    'title'        => $link_generare,
                                                                    'cost'         => 0,
                                                                    'tax_class_id' => 'fancourier.tax.'.str_replace(" ","_",$tip_serviciu[0]),
                                                                    'text'         => $msg.$page
                                                                );
																//print_r($quote_data);echo 4;exit;
                                                                $error = true;
                                                            }
                                                    }
                                            }

                                            unset($tip_serviciu);
                                        }
                                    }
	
                                    $method_data = array(
                                            'code'       => 'fancourier',
                                            'title'      => 'FAN Courier - Doar plata online cu card sau OP (1-2 zile)',
                                            'quote'      => $quote_data,
                                            'sort_order' => '1',
                                            'error'      => $error
                                    );
                                    if ($error != true)
                                   	 return $method_data;
                        }

                }
	}
}
?>
