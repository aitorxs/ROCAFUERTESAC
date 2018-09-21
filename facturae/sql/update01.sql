-- Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
-- Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
-- Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
-- Copyright (C) 2004      Guillaume Delecourt  <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
-- Copyright (C) 2007 	   Patrick Raguin       <patrick.raguin@gmail.com>
-- Copyright (C) 2014 	   Alexandre Spangaro   <alexandre.spangaro@gmail.com>
--
-- This program is free software WHERE rowid = 10; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation WHERE rowid = 10; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY WHERE10; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- Do not add comment at end of line. This file is parsed by install and -- are removed

--
-- Countries
--
ALTER TABLE llx_facturae_thirdparty ADD comprador varchar(50) DEFAULT NULL;

UPDATE llx_c_country SET code_iso = 'FRA' WHERE rowid = 1;
UPDATE llx_c_country SET code_iso = 'BEL' WHERE rowid = 2;
UPDATE llx_c_country SET code_iso = 'ITA' WHERE rowid = 3;
UPDATE llx_c_country SET code_iso = 'ESP' WHERE rowid = 4;
UPDATE llx_c_country SET code_iso = 'DEU' WHERE rowid = 5;
UPDATE llx_c_country SET code_iso = 'CHE' WHERE rowid = 6;
UPDATE llx_c_country SET code_iso = 'GBR' WHERE rowid = 7;
UPDATE llx_c_country SET code_iso = 'IRL' WHERE rowid = 8;
UPDATE llx_c_country SET code_iso = 'CHN' WHERE rowid = 9;
UPDATE llx_c_country SET code_iso = 'TUN' WHERE rowid = 10;
UPDATE llx_c_country SET code_iso = 'USA' WHERE rowid = 11;
UPDATE llx_c_country SET code_iso = 'MAR' WHERE rowid = 12;
UPDATE llx_c_country SET code_iso = 'DZA' WHERE rowid = 13;
UPDATE llx_c_country SET code_iso = 'CAN' WHERE rowid = 14;
UPDATE llx_c_country SET code_iso = 'TGO' WHERE rowid = 15;
UPDATE llx_c_country SET code_iso = 'GAB' WHERE rowid = 16;
UPDATE llx_c_country SET code_iso = 'NLD' WHERE rowid = 17;
UPDATE llx_c_country SET code_iso = 'HUN' WHERE rowid = 18;
UPDATE llx_c_country SET code_iso = 'RUS' WHERE rowid = 19;
UPDATE llx_c_country SET code_iso = 'SWE' WHERE rowid = 20;
UPDATE llx_c_country SET code_iso = 'CIV' WHERE rowid = 21;
UPDATE llx_c_country SET code_iso = 'SEN' WHERE rowid = 22;
UPDATE llx_c_country SET code_iso = 'ARG' WHERE rowid = 23;
UPDATE llx_c_country SET code_iso = 'CMR' WHERE rowid = 24;
UPDATE llx_c_country SET code_iso = 'PRT' WHERE rowid = 25;
UPDATE llx_c_country SET code_iso = 'SAU' WHERE rowid = 26;
UPDATE llx_c_country SET code_iso = 'MCO' WHERE rowid = 27;
UPDATE llx_c_country SET code_iso = 'AUS' WHERE rowid = 28;
UPDATE llx_c_country SET code_iso = 'SGP' WHERE rowid = 29;
UPDATE llx_c_country SET code_iso = 'AFG' WHERE rowid = 30;
UPDATE llx_c_country SET code_iso = 'ALA' WHERE rowid = 31;
UPDATE llx_c_country SET code_iso = 'ALB' WHERE rowid = 32;
UPDATE llx_c_country SET code_iso = 'ASM' WHERE rowid = 33;
UPDATE llx_c_country SET code_iso = 'AND' WHERE rowid = 34;
UPDATE llx_c_country SET code_iso = 'AGO' WHERE rowid = 35;
UPDATE llx_c_country SET code_iso = 'AIA' WHERE rowid = 36;
UPDATE llx_c_country SET code_iso = 'ATA' WHERE rowid = 37;
UPDATE llx_c_country SET code_iso = 'ATG' WHERE rowid = 38;
UPDATE llx_c_country SET code_iso = 'ARM' WHERE rowid = 39;
UPDATE llx_c_country SET code_iso = 'ABW' WHERE rowid = 40;
UPDATE llx_c_country SET code_iso = 'AUT' WHERE rowid = 41;
UPDATE llx_c_country SET code_iso = 'AZE' WHERE rowid = 42;
UPDATE llx_c_country SET code_iso = 'BHS' WHERE rowid = 43;
UPDATE llx_c_country SET code_iso = 'BHR' WHERE rowid = 44;
UPDATE llx_c_country SET code_iso = 'BGD' WHERE rowid = 45;
UPDATE llx_c_country SET code_iso = 'BRB' WHERE rowid = 46;
UPDATE llx_c_country SET code_iso = 'BLR' WHERE rowid = 47;
UPDATE llx_c_country SET code_iso = 'BLZ' WHERE rowid = 48;
UPDATE llx_c_country SET code_iso = 'BEN' WHERE rowid = 49;
UPDATE llx_c_country SET code_iso = 'BMU' WHERE rowid = 50;
UPDATE llx_c_country SET code_iso = 'BTN' WHERE rowid = 51;
UPDATE llx_c_country SET code_iso = 'BOL' WHERE rowid = 52;
UPDATE llx_c_country SET code_iso = 'BIH' WHERE rowid = 53;
UPDATE llx_c_country SET code_iso = 'BWA' WHERE rowid = 54;
UPDATE llx_c_country SET code_iso = 'BVT' WHERE rowid = 55;
UPDATE llx_c_country SET code_iso = 'BRA' WHERE rowid = 56;
UPDATE llx_c_country SET code_iso = 'IOT' WHERE rowid = 57;
UPDATE llx_c_country SET code_iso = 'BRN' WHERE rowid = 58;
UPDATE llx_c_country SET code_iso = 'BGR' WHERE rowid = 59;
UPDATE llx_c_country SET code_iso = 'BFA' WHERE rowid = 60;
UPDATE llx_c_country SET code_iso = 'BDI' WHERE rowid = 61;
UPDATE llx_c_country SET code_iso = 'KHM' WHERE rowid = 62;
UPDATE llx_c_country SET code_iso = 'CPV' WHERE rowid = 63;
UPDATE llx_c_country SET code_iso = 'CYM' WHERE rowid = 64;
UPDATE llx_c_country SET code_iso = 'CAF' WHERE rowid = 65;
UPDATE llx_c_country SET code_iso = 'TCD' WHERE rowid = 66;
UPDATE llx_c_country SET code_iso = 'CHL' WHERE rowid = 67;
UPDATE llx_c_country SET code_iso = 'CXR' WHERE rowid = 68;
UPDATE llx_c_country SET code_iso = 'CCK' WHERE rowid = 69;
UPDATE llx_c_country SET code_iso = 'COL' WHERE rowid = 70;
UPDATE llx_c_country SET code_iso = 'COM' WHERE rowid = 71;
UPDATE llx_c_country SET code_iso = 'COG' WHERE rowid = 72;
UPDATE llx_c_country SET code_iso = 'COD' WHERE rowid = 73;
UPDATE llx_c_country SET code_iso = 'COK' WHERE rowid = 74;
UPDATE llx_c_country SET code_iso = 'CRI' WHERE rowid = 75;
UPDATE llx_c_country SET code_iso = 'HRV' WHERE rowid = 76;
UPDATE llx_c_country SET code_iso = 'CUB' WHERE rowid = 77;
UPDATE llx_c_country SET code_iso = 'CYP' WHERE rowid = 78;
UPDATE llx_c_country SET code_iso = 'CZE' WHERE rowid = 79;
UPDATE llx_c_country SET code_iso = 'DNK' WHERE rowid = 80;
UPDATE llx_c_country SET code_iso = 'DJI' WHERE rowid = 81;
UPDATE llx_c_country SET code_iso = 'DMA' WHERE rowid = 82;
UPDATE llx_c_country SET code_iso = 'DOM' WHERE rowid = 83;
UPDATE llx_c_country SET code_iso = 'ECU' WHERE rowid = 84;
UPDATE llx_c_country SET code_iso = 'EGY' WHERE rowid = 85;
UPDATE llx_c_country SET code_iso = 'SLV' WHERE rowid = 86;
UPDATE llx_c_country SET code_iso = 'GNQ' WHERE rowid = 87;
UPDATE llx_c_country SET code_iso = 'ERI' WHERE rowid = 88;
UPDATE llx_c_country SET code_iso = 'EST' WHERE rowid = 89;
UPDATE llx_c_country SET code_iso = 'ETH' WHERE rowid = 90;
UPDATE llx_c_country SET code_iso = 'FLK' WHERE rowid = 91;
UPDATE llx_c_country SET code_iso = 'FRO' WHERE rowid = 92;
UPDATE llx_c_country SET code_iso = 'FJI' WHERE rowid = 93;
UPDATE llx_c_country SET code_iso = 'FIN' WHERE rowid = 94;
UPDATE llx_c_country SET code_iso = 'GUF' WHERE rowid = 95;
UPDATE llx_c_country SET code_iso = 'PYF' WHERE rowid = 96;
UPDATE llx_c_country SET code_iso = 'ATF' WHERE rowid = 97;
UPDATE llx_c_country SET code_iso = 'GMB' WHERE rowid = 98;
UPDATE llx_c_country SET code_iso = 'GEO' WHERE rowid = 99;
UPDATE llx_c_country SET code_iso = 'GHA' WHERE rowid = 100;
UPDATE llx_c_country SET code_iso = 'GIB' WHERE rowid = 101;
UPDATE llx_c_country SET code_iso = 'GRC' WHERE rowid = 102;
UPDATE llx_c_country SET code_iso = 'GRL' WHERE rowid = 103;
UPDATE llx_c_country SET code_iso = 'GRD' WHERE rowid = 104;
UPDATE llx_c_country SET code_iso = 'GUM' WHERE rowid = 106;
UPDATE llx_c_country SET code_iso = 'GTM' WHERE rowid = 107;
UPDATE llx_c_country SET code_iso = 'GIN' WHERE rowid = 108;
UPDATE llx_c_country SET code_iso = 'GNB' WHERE rowid = 109;
UPDATE llx_c_country SET code_iso = 'HTI' WHERE rowid = 111;
UPDATE llx_c_country SET code_iso = 'HMD' WHERE rowid = 112;
UPDATE llx_c_country SET code_iso = 'VAT' WHERE rowid = 113;
UPDATE llx_c_country SET code_iso = 'HND' WHERE rowid = 114;
UPDATE llx_c_country SET code_iso = 'HKG' WHERE rowid = 115;
UPDATE llx_c_country SET code_iso = 'ISL' WHERE rowid = 116;
UPDATE llx_c_country SET code_iso = 'IND' WHERE rowid = 117;
UPDATE llx_c_country SET code_iso = 'IDN' WHERE rowid = 118;
UPDATE llx_c_country SET code_iso = 'IRN' WHERE rowid = 119;
UPDATE llx_c_country SET code_iso = 'IRQ' WHERE rowid = 120;
UPDATE llx_c_country SET code_iso = 'ISR' WHERE rowid = 121;
UPDATE llx_c_country SET code_iso = 'JAM' WHERE rowid = 122;
UPDATE llx_c_country SET code_iso = 'JPN' WHERE rowid = 123;
UPDATE llx_c_country SET code_iso = 'JOR' WHERE rowid = 124;
UPDATE llx_c_country SET code_iso = 'KAZ' WHERE rowid = 125;
UPDATE llx_c_country SET code_iso = 'KEN' WHERE rowid = 126;
UPDATE llx_c_country SET code_iso = 'KIR' WHERE rowid = 127;
UPDATE llx_c_country SET code_iso = 'PRK' WHERE rowid = 128;
UPDATE llx_c_country SET code_iso = 'KOR' WHERE rowid = 129;
UPDATE llx_c_country SET code_iso = 'KWT' WHERE rowid = 130;
UPDATE llx_c_country SET code_iso = 'KGZ' WHERE rowid = 131;
UPDATE llx_c_country SET code_iso = 'LAO' WHERE rowid = 132;
UPDATE llx_c_country SET code_iso = 'LVA' WHERE rowid = 133;
UPDATE llx_c_country SET code_iso = 'LBN' WHERE rowid = 134;
UPDATE llx_c_country SET code_iso = 'LSO' WHERE rowid = 135;
UPDATE llx_c_country SET code_iso = 'LBR' WHERE rowid = 136;
UPDATE llx_c_country SET code_iso = 'LBY' WHERE rowid = 137;
UPDATE llx_c_country SET code_iso = 'LIE' WHERE rowid = 138;
UPDATE llx_c_country SET code_iso = 'LTU' WHERE rowid = 139;
UPDATE llx_c_country SET code_iso = 'LUX' WHERE rowid = 140;
UPDATE llx_c_country SET code_iso = 'MAC' WHERE rowid = 141;
UPDATE llx_c_country SET code_iso = 'MKD' WHERE rowid = 142;
UPDATE llx_c_country SET code_iso = 'MDG' WHERE rowid = 143;
UPDATE llx_c_country SET code_iso = 'MWI' WHERE rowid = 144;
UPDATE llx_c_country SET code_iso = 'MYS' WHERE rowid = 145;
UPDATE llx_c_country SET code_iso = 'MDV' WHERE rowid = 146;
UPDATE llx_c_country SET code_iso = 'MLI' WHERE rowid = 147;
UPDATE llx_c_country SET code_iso = 'MLT' WHERE rowid = 148;
UPDATE llx_c_country SET code_iso = 'MHL' WHERE rowid = 149;
UPDATE llx_c_country SET code_iso = 'MRT' WHERE rowid = 151;
UPDATE llx_c_country SET code_iso = 'MUS' WHERE rowid = 152;
UPDATE llx_c_country SET code_iso = 'MYT' WHERE rowid = 153;
UPDATE llx_c_country SET code_iso = 'MEX' WHERE rowid = 154;
UPDATE llx_c_country SET code_iso = 'FSM' WHERE rowid = 155;
UPDATE llx_c_country SET code_iso = 'MDA' WHERE rowid = 156;
UPDATE llx_c_country SET code_iso = 'MNG' WHERE rowid = 157;
UPDATE llx_c_country SET code_iso = 'MSR' WHERE rowid = 158;
UPDATE llx_c_country SET code_iso = 'MOZ' WHERE rowid = 159;
UPDATE llx_c_country SET code_iso = 'MMR' WHERE rowid = 160;
UPDATE llx_c_country SET code_iso = 'NAM' WHERE rowid = 161;
UPDATE llx_c_country SET code_iso = 'NRU' WHERE rowid = 162;
UPDATE llx_c_country SET code_iso = 'NPL' WHERE rowid = 163;
UPDATE llx_c_country SET code_iso = 'NCL' WHERE rowid = 165;
UPDATE llx_c_country SET code_iso = 'NZL' WHERE rowid = 166;
UPDATE llx_c_country SET code_iso = 'NIC' WHERE rowid = 167;
UPDATE llx_c_country SET code_iso = 'NER' WHERE rowid = 168;
UPDATE llx_c_country SET code_iso = 'NGA' WHERE rowid = 169;
UPDATE llx_c_country SET code_iso = 'NIU' WHERE rowid = 170;
UPDATE llx_c_country SET code_iso = 'NFK' WHERE rowid = 171;
UPDATE llx_c_country SET code_iso = 'MNP' WHERE rowid = 172;
UPDATE llx_c_country SET code_iso = 'NOR' WHERE rowid = 173;
UPDATE llx_c_country SET code_iso = 'OMN' WHERE rowid = 174;
UPDATE llx_c_country SET code_iso = 'PAK' WHERE rowid = 175;
UPDATE llx_c_country SET code_iso = 'PLW' WHERE rowid = 176;
UPDATE llx_c_country SET code_iso = 'PSE' WHERE rowid = 177;
UPDATE llx_c_country SET code_iso = 'PAN' WHERE rowid = 178;
UPDATE llx_c_country SET code_iso = 'PNG' WHERE rowid = 179;
UPDATE llx_c_country SET code_iso = 'PRY' WHERE rowid = 180;
UPDATE llx_c_country SET code_iso = 'PER' WHERE rowid = 181;
UPDATE llx_c_country SET code_iso = 'PHL' WHERE rowid = 182;
UPDATE llx_c_country SET code_iso = 'PCN' WHERE rowid = 183;
UPDATE llx_c_country SET code_iso = 'POL' WHERE rowid = 184;
UPDATE llx_c_country SET code_iso = 'PRI' WHERE rowid = 185;
UPDATE llx_c_country SET code_iso = 'QAT' WHERE rowid = 186;
UPDATE llx_c_country SET code_iso = 'ROU' WHERE rowid = 188;
UPDATE llx_c_country SET code_iso = 'RWA' WHERE rowid = 189;
UPDATE llx_c_country SET code_iso = 'SHN' WHERE rowid = 190;
UPDATE llx_c_country SET code_iso = 'KNA' WHERE rowid = 191;
UPDATE llx_c_country SET code_iso = 'LCA' WHERE rowid = 192;
UPDATE llx_c_country SET code_iso = 'SPM' WHERE rowid = 193;
UPDATE llx_c_country SET code_iso = 'VCT' WHERE rowid = 194;
UPDATE llx_c_country SET code_iso = 'WSM' WHERE rowid = 195;
UPDATE llx_c_country SET code_iso = 'SMR' WHERE rowid = 196;
UPDATE llx_c_country SET code_iso = 'STP' WHERE rowid = 197;
UPDATE llx_c_country SET code_iso = 'SRB' WHERE rowid = 198;
UPDATE llx_c_country SET code_iso = 'SYC' WHERE rowid = 199;
UPDATE llx_c_country SET code_iso = 'SLE' WHERE rowid = 200;
UPDATE llx_c_country SET code_iso = 'SVK' WHERE rowid = 201;
UPDATE llx_c_country SET code_iso = 'SVN' WHERE rowid = 202;
UPDATE llx_c_country SET code_iso = 'SLB' WHERE rowid = 203;
UPDATE llx_c_country SET code_iso = 'SOM' WHERE rowid = 204;
UPDATE llx_c_country SET code_iso = 'ZAF' WHERE rowid = 205;
UPDATE llx_c_country SET code_iso = 'SGS' WHERE rowid = 206;
UPDATE llx_c_country SET code_iso = 'LKA' WHERE rowid = 207;
UPDATE llx_c_country SET code_iso = 'SDN' WHERE rowid = 208;
UPDATE llx_c_country SET code_iso = 'SUR' WHERE rowid = 209;
UPDATE llx_c_country SET code_iso = 'SJM' WHERE rowid = 210;
UPDATE llx_c_country SET code_iso = 'SWZ' WHERE rowid = 211;
UPDATE llx_c_country SET code_iso = 'SYR' WHERE rowid = 212;
UPDATE llx_c_country SET code_iso = 'TWN' WHERE rowid = 213;
UPDATE llx_c_country SET code_iso = 'TJK' WHERE rowid = 214;
UPDATE llx_c_country SET code_iso = 'TZA' WHERE rowid = 215;
UPDATE llx_c_country SET code_iso = 'THA' WHERE rowid = 216;
UPDATE llx_c_country SET code_iso = 'TLS' WHERE rowid = 217;
UPDATE llx_c_country SET code_iso = 'TKL' WHERE rowid = 218;
UPDATE llx_c_country SET code_iso = 'TON' WHERE rowid = 219;
UPDATE llx_c_country SET code_iso = 'TTO' WHERE rowid = 220;
UPDATE llx_c_country SET code_iso = 'TUR' WHERE rowid = 221;
UPDATE llx_c_country SET code_iso = 'TKM' WHERE rowid = 222;
UPDATE llx_c_country SET code_iso = 'TCA' WHERE rowid = 223;
UPDATE llx_c_country SET code_iso = 'TUV' WHERE rowid = 224;
UPDATE llx_c_country SET code_iso = 'UGA' WHERE rowid = 225;
UPDATE llx_c_country SET code_iso = 'UKR' WHERE rowid = 226;
UPDATE llx_c_country SET code_iso = 'ARE' WHERE rowid = 227;
UPDATE llx_c_country SET code_iso = 'UMI' WHERE rowid = 228;
UPDATE llx_c_country SET code_iso = 'URY' WHERE rowid = 229;
UPDATE llx_c_country SET code_iso = 'UZB' WHERE rowid = 230;
UPDATE llx_c_country SET code_iso = 'VUT' WHERE rowid = 231;
UPDATE llx_c_country SET code_iso = 'VEN' WHERE rowid = 232;
UPDATE llx_c_country SET code_iso = 'VNM' WHERE rowid = 233;
UPDATE llx_c_country SET code_iso = 'VGB' WHERE rowid = 234;
UPDATE llx_c_country SET code_iso = 'VIR' WHERE rowid = 235;
UPDATE llx_c_country SET code_iso = 'WLF' WHERE rowid = 236;
UPDATE llx_c_country SET code_iso = 'ESH' WHERE rowid = 237;
UPDATE llx_c_country SET code_iso = 'YEM' WHERE rowid = 238;
UPDATE llx_c_country SET code_iso = 'ZMB' WHERE rowid = 239;
UPDATE llx_c_country SET code_iso = 'ZWE' WHERE rowid = 240;
UPDATE llx_c_country SET code_iso = 'GGY' WHERE rowid = 241;
UPDATE llx_c_country SET code_iso = 'IMN' WHERE rowid = 242;
UPDATE llx_c_country SET code_iso = 'JEY' WHERE rowid = 243;
UPDATE llx_c_country SET code_iso = 'MNE' WHERE rowid = 244;
UPDATE llx_c_country SET code_iso = 'BLM' WHERE rowid = 245;
UPDATE llx_c_country SET code_iso = 'MAF' WHERE rowid = 246;